/** Remembers the phone number between the login and OTP screens (kept out of the URL). */
const KEY = "mmn.loginPhone";

export const loginPhone = {
  get: () => (typeof window === "undefined" ? null : window.sessionStorage.getItem(KEY)),
  set: (phone: string) => window.sessionStorage.setItem(KEY, phone),
  clear: () => window.sessionStorage.removeItem(KEY),
};
