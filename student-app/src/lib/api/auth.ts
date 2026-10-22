/** Login with mobile number + OTP. Backend: app/Http/Controllers/Api/V1/AuthController.php */
import { USE_MOCK, mockDelay, request, tokenStore } from "./client";
import { readState, writeState } from "../mock/store";

export async function sendOtp(phone: string): Promise<void> {
  if (USE_MOCK) return mockDelay(500);
  await request("/auth/otp/send", { method: "POST", body: JSON.stringify({ phone }) });
}

/** Returns whether the student still has to complete their profile. */
export async function verifyOtp(phone: string, code: string): Promise<{ needsProfile: boolean }> {
  if (USE_MOCK) {
    await mockDelay(500);
    if (!/^\d{6}$/.test(code)) throw new Error("Enter the 6-digit code sent to your phone");
    const { student } = readState();
    if (!student || student.phone !== phone) {
      writeState((s) => ({ ...s, student: { id: 1, phone, name: "", nickname: "", email: "", targetExam: "", attemptYear: 0 } }));
      return { needsProfile: true };
    }
    return { needsProfile: !student.name };
  }
  const data = await request<{ token: string; needsProfile: boolean }>("/auth/otp/verify", {
    method: "POST",
    body: JSON.stringify({ phone, code, device_name: navigator.userAgent }),
  });
  tokenStore.set(data.token);
  return { needsProfile: data.needsProfile };
}

export async function logout(): Promise<void> {
  if (!USE_MOCK) await request("/auth/logout", { method: "POST" }).catch(() => undefined);
  tokenStore.clear();
}
