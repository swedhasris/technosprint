import { execute } from "./db";

async function run() {
  console.log("Cleaning up MySQL/SQLite tickets...");
  try {
    const result = await execute("DELETE FROM tickets");
    console.log("Success:", result);
  } catch (err) {
    console.error("Failed:", err.message);
  }
  process.exit(0);
}
run();
