import { redirect } from "next/navigation";

/** Root URL → the student's courses (the shell sends logged-out users to /login once the API is live). */
export default function Home() {
  redirect("/my-courses");
}
