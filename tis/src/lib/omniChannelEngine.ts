import nodemailer from 'nodemailer';
import imaps from 'imap-simple';
import { simpleParser } from 'mailparser';
import fs from 'fs';
import path from 'path';
import { query, execute, formatDate } from './db';
import { NotificationEngine } from "./notificationEngine";
import { collection, addDoc, serverTimestamp, getDocs, query as fsQuery, where, limit, updateDoc, doc } from "firebase/firestore";
import { db as firestoreDb } from "./firebase";


/**
 * OmniChannelEngine handles multi-channel communication (Email, WhatsApp, etc.)
 */
export class OmniChannelEngine {
  private static transporter: nodemailer.Transporter | null = null;

  /**
   * Initializes the email transporter using environment variables
   */
  private static getTransporter() {
    if (!this.transporter) {
      this.transporter = nodemailer.createTransport({
        host: process.env.SMTP_HOST || 'smtp.gmail.com',
        port: parseInt(process.env.SMTP_PORT || '587'),
        secure: process.env.SMTP_PORT === '465',
        auth: {
          user: process.env.SMTP_USER || 'Support@technosprint.net',
          pass: process.env.SMTP_PASS,
        },
        tls: {
          rejectUnauthorized: false
        }
      });
    }
    return this.transporter;
  }

  /**
   * Polls incoming emails and converts them to tickets or comments.
   */
  static async pollIncomingEmails() {
    console.log('[OmniChannel] Starting email polling...');
    
    const config = {
      imap: {
        user: process.env.SMTP_USER || 'Support@technosprint.net',
        password: process.env.SMTP_PASS || '',
        host: process.env.SMTP_HOST?.replace('smtp.', 'imap.') || 'imap.gmail.com',
        port: 993,
        tls: true,
        tlsOptions: { rejectUnauthorized: false },
        authTimeout: 5000,
      }
    };

    if (!config.imap.user || !config.imap.password) {
      console.log('[OmniChannel] Email credentials missing. Skipping poll.');
      return;
    }

    try {
      const connection = await imaps.connect(config);
      await connection.openBox('INBOX');

      const searchCriteria = ['UNSEEN'];
      const fetchOptions = {
        bodies: ['HEADER', 'TEXT', ''],
        markSeen: true
      };

      const messages = await connection.search(searchCriteria, fetchOptions);
      console.log(`[OmniChannel] Found ${messages.length} new emails.`);

      for (const item of messages) {
        const all = item.parts.find(part => part.which === '');
        const id = item.attributes.uid;

        if (all) {
          const parsed = await simpleParser(all.body);
          await this.processIncomingEmail(parsed);
        }
      }

      connection.end();
    } catch (error: any) {
      console.error('[OmniChannel] Email polling error:', error.message);
    }
  }

  /**
   * Process a single incoming email
   */
  private static async processIncomingEmail(mail: any) {
    const subject = mail.subject || '(No Subject)';
    const from = mail.from?.text || mail.from?.value?.[0]?.address || 'unknown';
    const body = mail.text || mail.html || '';
    const messageId = mail.messageId;

    console.log(`[OmniChannel] Processing email from ${from}: ${subject}`);

    try {
      // 0. Handle Attachments
      const attachments: any[] = [];
      if (mail.attachments && mail.attachments.length > 0) {
        const uploadsDir = path.join(process.cwd(), 'public', 'uploads');
        if (!fs.existsSync(uploadsDir)) fs.mkdirSync(uploadsDir, { recursive: true });

        for (const att of mail.attachments) {
          const filename = `${Date.now()}-${att.filename || 'attachment'}`;
          const filepath = path.join(uploadsDir, filename);
          fs.writeFileSync(filepath, att.content);
          attachments.push({
            filename: att.filename || 'attachment',
            stored_filename: filename,
            content_type: att.contentType,
            size: att.size,
            url: `/uploads/${filename}`
          });
        }
      }

      // 1. Check if this is a reply to an existing ticket
      // Match formats: INC1234567 or [TK-1234567]
      const ticketMatch = subject.match(/INC(\d+)/i) || subject.match(/\[TK-(\d+)\]/i) || body.match(/INC(\d+)/i);
      
      if (ticketMatch) {
        let ticketNumber = ticketMatch[0].toUpperCase();
        if (ticketNumber.startsWith('[TK-')) {
          ticketNumber = ticketNumber.replace('[TK-', 'INC').replace(']', '');
        }

        const tickets = await query("SELECT id, assigned_to, ticket_number, title FROM tickets WHERE ticket_number = ?", [ticketNumber]);
        
        if (tickets.length > 0) {
          const ticketSqlId = tickets[0].id;
          const assignedTo = tickets[0].assigned_to;
          console.log(`[OmniChannel] Found matching ticket ${ticketNumber}. Adding reply.`);
          
          const activityData = {
            subject,
            from,
            messageId,
            body: body.substring(0, 5000),
            attachments,
            timestamp: new Date().toISOString()
          };

          // Update SQL Timeline
          await execute(
            "INSERT INTO ticket_activities (ticket_id, activity_type, visibility_type, created_by, created_by_name, message, metadata_json) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [ticketSqlId, 'email_received', 'public', from, from, "New email reply received", JSON.stringify(activityData)]
          );
          
          await execute("UPDATE tickets SET updated_at = ? WHERE id = ?", [formatDate(new Date()), ticketSqlId]);

          // Sync to Firestore if possible (to update UI instantly)
          try {
            const fsTickets = await getDocs(fsQuery(collection(firestoreDb, "tickets"), where("number", "==", ticketNumber), limit(1)));
            if (!fsTickets.empty) {
              const fsDoc = fsTickets.docs[0];
              const currentHistory = fsDoc.data().history || [];
              await updateDoc(doc(firestoreDb, "tickets", fsDoc.id), {
                updatedAt: serverTimestamp(),
                history: [...currentHistory, {
                  action: `Email Reply Received from ${from}`,
                  timestamp: new Date().toISOString(),
                  user: from,
                  details: subject
                }]
              });
              
              // Add to activities collection if it exists
              await addDoc(collection(firestoreDb, "activities"), {
                ticketId: fsDoc.id,
                activity_type: 'email_received',
                created_by: from,
                created_by_name: from,
                message: body.substring(0, 1000),
                timestamp: serverTimestamp(),
                metadata: activityData
              });
            }
          } catch (fsErr) {
            console.error("[OmniChannel] Firestore sync error:", fsErr);
          }

          // Notify assigned person
          if (assignedTo) {
            await NotificationEngine.create(
              assignedTo,
              `New Reply: ${ticketNumber}`,
              `Client ${from} replied to ${ticketNumber}. Check the activity timeline.`,
              'email_reply',
              ticketNumber
            );
          }
          return;
        }
      }

      // 2. If no ticket match, create a new ticket
      console.log(`[OmniChannel] No ticket match. Creating new ticket for ${from}.`);
      
      const ticketNumber = 'INC' + Math.floor(1000000 + Math.random() * 9000000);
      const now = new Date();
      
      // SQL Insertion
      const sqlResult = await execute(
        "INSERT INTO tickets (ticket_number, caller, title, description, status, priority, channel, created_by, created_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [ticketNumber, from, subject, body.substring(0, 5000), 'New', '4 - Low', 'Email', from, from]
      );

      const ticketSqlId = sqlResult.insertId;

      await execute(
        "INSERT INTO ticket_activities (ticket_id, activity_type, visibility_type, created_by, created_by_name, message, metadata_json) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [ticketSqlId, 'email_received', 'public', from, from, "Ticket created from email", JSON.stringify({
          subject,
          from,
          messageId,
          body: body.substring(0, 5000),
          attachments
        })]
      );

      // Firestore Insertion (Instant UI Sync)
      try {
        const ticketData = {
          number: ticketNumber,
          caller: from,
          title: subject,
          description: body.substring(0, 5000),
          status: "New",
          priority: "4 - Low",
          channel: "Email",
          createdBy: "System",
          createdAt: serverTimestamp(),
          updatedAt: serverTimestamp(),
          responseDeadline: new Date(now.getTime() + 4 * 60 * 60 * 1000).toISOString(),
          resolutionDeadline: null,
          responseSlaStatus: "In Progress",
          resolutionSlaStatus: "Pending",
          history: [{ 
            action: "Ticket created from email", 
            timestamp: now.toISOString(), 
            user: from 
          }],
          attachments: attachments
        };
        await addDoc(collection(firestoreDb, "tickets"), ticketData);
      } catch (fsErr) {
        console.error("[OmniChannel] Firestore create error:", fsErr);
      }

      // Notify all admins/agents
      const admins = await query("SELECT uid FROM users WHERE role IN ('admin', 'agent', 'super_admin', 'ultra_super_admin')");
      for (const admin of admins) {
        await NotificationEngine.create(
          admin.uid,
          "New Ticket Received",
          `${from} created a new ticket via email: ${ticketNumber}`,
          'ticket_created',
          ticketNumber
        );
      }

      // Send auto-acknowledgement
      await this.sendEmail(from, `[TK-${ticketNumber.replace('INC', '')}] Ticket Created: ${subject}`, `
        <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #2563eb;">Technosprint Support</h2>
          <p>Hello,</p>
          <p>We have received your email and a new support ticket has been opened for you.</p>
          <div style="background-color: #f8fafc; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #e2e8f0;">
            <p style="margin: 0;"><strong>Ticket Number:</strong> TK-${ticketNumber.replace('INC', '')}</p>
            <p style="margin: 5px 0 0 0;"><strong>Subject:</strong> ${subject}</p>
          </div>
          <p>Our team will review your request shortly. Please include the Ticket ID <strong>[TK-${ticketNumber.replace('INC', '')}]</strong> in all future communications regarding this matter.</p>
          <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
          <p style="font-size: 12px; color: #64748b;">This is an automated notification from Support@technosprint.net. Please do not remove the Ticket ID from the subject line when replying.</p>
        </div>
      `, attachments);

    } catch (error: any) {
      console.error('[OmniChannel] Error processing email:', error.message);
    }
  }

  /**
   * Sends an email
   */
  static async sendEmail(to: string, subject: string, html: string, attachments: any[] = []) {
    try {
      const transporter = this.getTransporter();
      const fromEmail = process.env.SMTP_USER || 'Support@technosprint.net';
      const fromName = "Technosprint Support";

      const mailOptions: any = {
        from: `"${fromName}" <${fromEmail}>`,
        to,
        subject,
        html,
      };

      if (attachments && attachments.length > 0) {
        mailOptions.attachments = attachments.map(att => ({
          filename: att.filename,
          path: att.url.startsWith('http') ? att.url : path.join(process.cwd(), 'public', att.url)
        }));
      }

      const info = await transporter.sendMail(mailOptions);

      console.log(`[OmniChannel] Email sent to ${to}: ${info.messageId}`);
      return info;
    } catch (error: any) {
      console.error('[OmniChannel] Send email error:', error.message);
      throw error;
    }
  }

  /**
   * Processes the notification queue to send pending emails/WhatsApp messages.
   */
  static async processNotificationQueue() {
    // console.log('[OmniChannel] Processing notification queue...');
    try {
      // In a real implementation, we would fetch unsent notifications from the DB
      // and send them here. For now, we just ensure the engine is alive.
    } catch (error: any) {
      console.error('[OmniChannel] Notification queue error:', error.message);
    }
  }
}
