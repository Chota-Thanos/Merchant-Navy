"use client";

import { useState } from "react";
import { Mail } from "lucide-react";
import type { ProfileInput } from "@/lib/api/profile";
import { attemptYears, targetExams } from "@/lib/config";
import { Button } from "@/components/ui/Button";
import { Field, Input, Select } from "@/components/ui/Field";

type Errors = Partial<Record<keyof ProfileInput, string>>;

function validate(v: ProfileInput): Errors {
  const e: Errors = {};
  if (v.name.trim().length < 2) e.name = "Enter your full name";
  if (!v.nickname.trim()) e.nickname = "Enter what we should call you";
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.email)) e.email = "Enter a valid email address";
  if (!v.targetExam) e.targetExam = "Choose your target exam";
  if (!v.attemptYear) e.attemptYear = "Choose your attempt year";
  return e;
}

/** Shared by first-time onboarding and the My Profile page. */
export function ProfileForm({
  initial,
  phone,
  submitLabel,
  onSubmit,
}: {
  initial: ProfileInput;
  phone: string;
  submitLabel: string;
  onSubmit: (values: ProfileInput) => Promise<void>;
}) {
  const [values, setValues] = useState<ProfileInput>(initial);
  const [errors, setErrors] = useState<Errors>({});
  const [saving, setSaving] = useState(false);

  const set = <K extends keyof ProfileInput>(key: K, value: ProfileInput[K]) => {
    setValues((v) => ({ ...v, [key]: value }));
    setErrors((e) => ({ ...e, [key]: undefined }));
  };

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    const found = validate(values);
    setErrors(found);
    if (Object.keys(found).length) return;
    setSaving(true);
    try {
      await onSubmit(values);
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={submit} className="space-y-5" noValidate>
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="Full name" required error={errors.name}>
          <Input value={values.name} onChange={(e) => set("name", e.target.value)} placeholder="Aarav Sharma" autoComplete="name" />
        </Field>
        <Field label="Nickname" required error={errors.nickname} hint="What we call you on the dashboard">
          <Input value={values.nickname} onChange={(e) => set("nickname", e.target.value)} placeholder="Aarav" />
        </Field>
      </div>

      <Field label="Mobile number" hint="Your login number can't be changed here. Contact support to change it.">
        <Input value={`+91 ${phone}`} disabled />
      </Field>

      <Field label="Email address" required error={errors.email}>
        <Input type="email" value={values.email} onChange={(e) => set("email", e.target.value)} placeholder="you@example.com" autoComplete="email" />
      </Field>
      <div className="-mt-2 flex gap-2.5 rounded-lg bg-brand-50 px-3.5 py-3 text-xs leading-relaxed text-brand-700">
        <Mail className="mt-0.5 size-4 shrink-0" />
        <span>Please use an email you check regularly. We&apos;ll send course updates, live class reminders, results and important notices there.</span>
      </div>

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="Target exam" required error={errors.targetExam}>
          <Select value={values.targetExam} onChange={(e) => set("targetExam", e.target.value)}>
            <option value="">Select exam</option>
            {targetExams.map((x) => (
              <option key={x}>{x}</option>
            ))}
          </Select>
        </Field>
        <Field label="Year of attempt" required error={errors.attemptYear}>
          <Select value={values.attemptYear || ""} onChange={(e) => set("attemptYear", Number(e.target.value))}>
            <option value="">Select year</option>
            {attemptYears().map((y) => (
              <option key={y} value={y}>
                {y}
              </option>
            ))}
          </Select>
        </Field>
      </div>

      <Button type="submit" size="lg" className="w-full sm:w-auto" loading={saving}>
        {submitLabel}
      </Button>
    </form>
  );
}
