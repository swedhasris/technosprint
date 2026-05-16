import { initializeApp } from "firebase/app";
import { getFirestore, collection, getDocs, deleteDoc, doc } from "firebase/firestore";
import firebaseConfig from "../../firebase-applet-config.json";
import sqlite3 from 'sqlite3';
import { open } from 'sqlite';

async function cleanup() {
  console.log("Starting ticket cleanup...");

  // 1. Delete from Firestore
  try {
    const app = initializeApp(firebaseConfig);
    const db = getFirestore(app);
    const querySnapshot = await getDocs(collection(db, "tickets"));
    console.log(`Firestore: Found ${querySnapshot.size} tickets. Deleting...`);
    const promises = querySnapshot.docs.map(d => deleteDoc(doc(db, "tickets", d.id)));
    await Promise.all(promises);
    console.log("Firestore: All tickets deleted.");
  } catch (err) {
    console.error("Firestore cleanup failed:", err.message);
  }

  // 2. Delete from SQLite (timesheet.sqlite)
  try {
    const db = await open({
      filename: './timesheet.sqlite',
      driver: sqlite3.Database
    });
    const result = await db.run("DELETE FROM tickets");
    console.log(`SQLite: Deleted ${result.changes} tickets.`);
    await db.close();
  } catch (err) {
    console.warn("SQLite cleanup failed (maybe table doesn't exist or file missing):", err.message);
  }

  console.log("Cleanup process finished.");
  process.exit(0);
}

cleanup();
