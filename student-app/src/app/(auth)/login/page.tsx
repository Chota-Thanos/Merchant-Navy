"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { ArrowRight, Smartphone } from "lucide-react";
import * as api from "@/lib/api";
import { loginPhone } from "@/lib/login-session";
import { Button } from "@/components/ui/Button";

export default function LoginPage() {
  const router = useRouter();
  const [phone, setPhone] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const valid = /^[6-9]\d{9}$/.test(phone);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!valid) return setError("Enter a valid 10-digit mobile number");
    setLoading(true);
    try {
      await api.auth.sendOtp(phone);
      loginPhone.set(phone);
      router.push("/login/verify");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not send OTP");
      setLoading(false);
    }
  }

  return (
    <div>
      <div className="mb-6 grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
        <Smartphone className="size-6" />
      </div>
      <h2 className="text-3xl font-extrabold text-navy-900">Welcome aboard</h2>
      <p className="mt-2 text-slate-500">Log in with your mobile number. We&apos;ll send you a one-time password.</p>

      <form onSubmit={submit} className="mt-8 space-y-5">
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold text-navy-900">Mobile number</span>
          <div className="flex h-12 overflow-hidden rounded-lg border border-line focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-100">
            <span className="grid place-items-center border-r border-line bg-slate-50 px-3.5 text-sm font-semibold text-slate-600">+91</span>
            <input
              autoFocus
              inputMode="numeric"
              autoComplete="tel-national"
              placeholder="98765 43210"
              maxLength={10}
              value={phone}
              onChange={(e) => {
                setPhone(e.target.value.replace(/\D/g, ""));
                setError("");
              }}
              className="flex-1 px-3.5 text-base tracking-wide text-navy-900 outline-none placeholder:text-slate-400"
            />
          </div>
          {error && <span className="mt-1.5 block text-xs font-medium text-red-600">{error}</span>}
        </label>

        <Button type="submit" size="lg" className="w-full" loading={loading} disabled={!valid}>
          Send OTP <ArrowRight className="size-4" />
        </Button>
      </form>

      <p className="mt-8 text-xs leading-relaxed text-slate-500">
        Use the same mobile number you used while purchasing your course. Your courses appear here once our team assigns them to your account.
      </p>
    </div>
  );
}
