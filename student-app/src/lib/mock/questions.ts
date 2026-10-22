/**
 * MOCK question bank (replaced by the API later).
 * Text is HTML with LaTeX: $...$ inline, $$...$$ on its own line.
 * `answer` is the index (0–3) of the correct option. On the real backend the
 * answer is never sent to the browser until the attempt is submitted.
 */

export interface MockQuestion {
  id: number;
  body: string;
  options: [string, string, string, string];
  answer: 0 | 1 | 2 | 3;
  explanation: string;
}

export const questionBank: MockQuestion[] = [
  // ---------- Physics ----------
  {
    id: 1,
    body: "The SI unit of surface tension is:",
    options: ["$\\mathrm{N/m^2}$", "$\\mathrm{N/m}$", "$\\mathrm{J/m}$", "$\\mathrm{kg\\,m/s}$"],
    answer: 1,
    explanation:
      "Surface tension is force per unit length: $$T = \\frac{F}{L}$$ so its SI unit is $\\mathrm{N/m}$ (equivalently $\\mathrm{J/m^2}$).",
  },
  {
    id: 2,
    body: "The dimensional formula of Planck's constant $h$ is:",
    options: ["$[MLT^{-1}]$", "$[ML^2T^{-2}]$", "$[ML^2T^{-1}]$", "$[ML^{-1}T^{-1}]$"],
    answer: 2,
    explanation:
      "From $E = h\\nu$, we get $$h = \\frac{E}{\\nu} = \\frac{[ML^2T^{-2}]}{[T^{-1}]} = [ML^2T^{-1}]$$",
  },
  {
    id: 3,
    body: "A ball is projected with speed $u = 20\\,\\text{m/s}$ at an angle of $30^\\circ$ with the horizontal. Its maximum height is ($g = 10\\,\\text{m/s}^2$):",
    options: ["$5\\,\\text{m}$", "$10\\,\\text{m}$", "$15\\,\\text{m}$", "$20\\,\\text{m}$"],
    answer: 0,
    explanation:
      "$$H = \\frac{u^2 \\sin^2\\theta}{2g} = \\frac{(20)^2 \\cdot \\left(\\tfrac{1}{2}\\right)^2}{2 \\times 10} = \\frac{400 \\times \\tfrac14}{20} = 5\\,\\text{m}$$",
  },
  {
    id: 4,
    body: "A body falls freely from rest. The distance it covers in the $3^{\\text{rd}}$ second is ($g = 9.8\\,\\text{m/s}^2$):",
    options: ["$24.5\\,\\text{m}$", "$44.1\\,\\text{m}$", "$29.4\\,\\text{m}$", "$14.7\\,\\text{m}$"],
    answer: 0,
    explanation:
      "Distance in the $n^{\\text{th}}$ second: $$s_n = u + \\frac{g}{2}(2n - 1) = 0 + 4.9 \\times 5 = 24.5\\,\\text{m}$$",
  },
  {
    id: 5,
    body: "Two resistors of $6\\,\\Omega$ each are connected in parallel. The equivalent resistance is:",
    options: ["$12\\,\\Omega$", "$3\\,\\Omega$", "$6\\,\\Omega$", "$1.5\\,\\Omega$"],
    answer: 1,
    explanation: "$$\\frac{1}{R} = \\frac{1}{6} + \\frac{1}{6} = \\frac{1}{3} \\Rightarrow R = 3\\,\\Omega$$",
  },
  {
    id: 6,
    body: "A ship floating in sea water sails into a fresh-water river. The ship will:",
    options: ["Rise a little", "Sink a little", "Stay at the same level", "Sink completely"],
    answer: 1,
    explanation:
      "The buoyant force must still equal the ship's weight: $\\rho_{\\text{water}} V_{\\text{sub}} g = W$. Fresh water is less dense than sea water, so the submerged volume $V_{\\text{sub}}$ increases and the ship sinks a little.",
  },
  {
    id: 7,
    body: "The time period of a simple pendulum is $T = 2\\pi\\sqrt{\\dfrac{L}{g}}$. If its length is made four times, the period:",
    options: ["Becomes half", "Doubles", "Becomes four times", "Remains unchanged"],
    answer: 1,
    explanation: "$T \\propto \\sqrt{L}$, so $$\\frac{T'}{T} = \\sqrt{\\frac{4L}{L}} = 2$$ The period doubles.",
  },
  {
    id: 8,
    body: "If the linear momentum of a body is doubled, its kinetic energy becomes:",
    options: ["Double", "Half", "Four times", "Unchanged"],
    answer: 2,
    explanation: "$$K = \\frac{p^2}{2m} \\Rightarrow K \\propto p^2$$ Doubling $p$ makes $K$ four times.",
  },
  // ---------- Chemistry ----------
  {
    id: 9,
    body: "The number of moles in $22\\,\\text{g}$ of $\\mathrm{CO_2}$ is:",
    options: ["$0.25$", "$0.5$", "$1$", "$2$"],
    answer: 1,
    explanation: "Molar mass of $\\mathrm{CO_2} = 12 + 2(16) = 44\\,\\text{g/mol}$. $$n = \\frac{22}{44} = 0.5\\,\\text{mol}$$",
  },
  {
    id: 10,
    body: "The pH of a $10^{-3}\\,\\text{M}$ solution of $\\mathrm{HCl}$ is:",
    options: ["$1$", "$3$", "$11$", "$7$"],
    answer: 1,
    explanation: "HCl is a strong acid, so $[\\mathrm{H^+}] = 10^{-3}$. $$\\text{pH} = -\\log_{10}(10^{-3}) = 3$$",
  },
  {
    id: 11,
    body: "The hybridisation of carbon in methane $(\\mathrm{CH_4})$ is:",
    options: ["$sp$", "$sp^2$", "$sp^3$", "$sp^3d$"],
    answer: 2,
    explanation: "Carbon forms four $\\sigma$ bonds and has no lone pair, so it is $sp^3$ hybridised (tetrahedral, $109.5^\\circ$).",
  },
  {
    id: 12,
    body: "The number of molecules in $18\\,\\text{g}$ of water is:",
    options: ["$6.022 \\times 10^{23}$", "$3.011 \\times 10^{23}$", "$1.2044 \\times 10^{24}$", "$18 \\times 10^{23}$"],
    answer: 0,
    explanation: "$18\\,\\text{g}$ of $\\mathrm{H_2O}$ is exactly $1$ mole, which contains $N_A = 6.022 \\times 10^{23}$ molecules.",
  },
  {
    id: 13,
    body: "The oxidation state of Mn in $\\mathrm{KMnO_4}$ is:",
    options: ["$+2$", "$+4$", "$+6$", "$+7$"],
    answer: 3,
    explanation: "$$(+1) + x + 4(-2) = 0 \\Rightarrow x = +7$$",
  },
  // ---------- Mathematics ----------
  {
    id: 14,
    body: "$$\\int_{0}^{\\pi/2} \\sin^2 x \\, dx = \\;?$$",
    options: ["$\\dfrac{\\pi}{2}$", "$\\dfrac{\\pi}{4}$", "$1$", "$\\dfrac{\\pi}{8}$"],
    answer: 1,
    explanation:
      "Use $\\sin^2 x = \\dfrac{1 - \\cos 2x}{2}$: $$\\int_0^{\\pi/2} \\frac{1-\\cos 2x}{2}\\,dx = \\left[\\frac{x}{2} - \\frac{\\sin 2x}{4}\\right]_0^{\\pi/2} = \\frac{\\pi}{4}$$",
  },
  {
    id: 15,
    body: "$$\\lim_{x \\to 0} \\frac{\\sin 3x}{x} = \\;?$$",
    options: ["$0$", "$1$", "$3$", "$\\dfrac{1}{3}$"],
    answer: 2,
    explanation: "$$\\lim_{x\\to0}\\frac{\\sin 3x}{x} = 3 \\cdot \\lim_{x\\to0}\\frac{\\sin 3x}{3x} = 3 \\times 1 = 3$$",
  },
  {
    id: 16,
    body: "If $\\begin{vmatrix} 2 & 3 \\\\ 1 & x \\end{vmatrix} = 5$, then $x$ equals:",
    options: ["$1$", "$2$", "$4$", "$-4$"],
    answer: 2,
    explanation: "$$2x - 3 = 5 \\Rightarrow x = 4$$",
  },
  {
    id: 17,
    body: "$\\dfrac{d}{dx}\\left(e^{x}\\sin x\\right)$ is:",
    options: ["$e^x \\cos x$", "$e^x(\\sin x + \\cos x)$", "$e^x(\\sin x - \\cos x)$", "$e^x \\sin x$"],
    answer: 1,
    explanation: "Product rule: $$\\frac{d}{dx}(e^x \\sin x) = e^x \\sin x + e^x \\cos x = e^x(\\sin x + \\cos x)$$",
  },
  {
    id: 18,
    body: "If $\\alpha$ and $\\beta$ are the roots of $x^2 - 5x + 6 = 0$, then $\\alpha^2 + \\beta^2$ is:",
    options: ["$13$", "$25$", "$12$", "$37$"],
    answer: 0,
    explanation: "$\\alpha + \\beta = 5$, $\\alpha\\beta = 6$. $$\\alpha^2 + \\beta^2 = (\\alpha+\\beta)^2 - 2\\alpha\\beta = 25 - 12 = 13$$",
  },
  {
    id: 19,
    body: "$$\\sum_{k=1}^{10} k = \\;?$$",
    options: ["$45$", "$50$", "$55$", "$100$"],
    answer: 2,
    explanation: "$$\\sum_{k=1}^{n} k = \\frac{n(n+1)}{2} = \\frac{10 \\times 11}{2} = 55$$",
  },
  {
    id: 20,
    body: "Two fair dice are thrown. The probability that the sum is $7$ is:",
    options: ["$\\dfrac{1}{6}$", "$\\dfrac{1}{12}$", "$\\dfrac{5}{36}$", "$\\dfrac{7}{36}$"],
    answer: 0,
    explanation:
      "Favourable outcomes: $(1,6),(2,5),(3,4),(4,3),(5,2),(6,1)$ — that is $6$ out of $36$. $$P = \\frac{6}{36} = \\frac{1}{6}$$",
  },
  // ---------- English & Aptitude ----------
  {
    id: 21,
    body: "Choose the word closest in meaning to <b>NAVIGATE</b>:",
    options: ["Steer", "Anchor", "Drift", "Sink"],
    answer: 0,
    explanation: "To <i>navigate</i> is to plan and direct the course of a ship — to <b>steer</b>.",
  },
  {
    id: 22,
    body: "Choose the correctly spelt word:",
    options: ["Acommodation", "Accomodation", "Accommodation", "Acomodation"],
    answer: 2,
    explanation: "<b>Accommodation</b> has a double <i>c</i> and a double <i>m</i>.",
  },
  {
    id: 23,
    body: "Choose the word opposite in meaning to <b>BUOYANT</b> (cheerful):",
    options: ["Cheerful", "Gloomy", "Floating", "Lively"],
    answer: 1,
    explanation: "<i>Buoyant</i> (of mood) means cheerful and optimistic; its opposite is <b>gloomy</b>.",
  },
  {
    id: 24,
    body: "A ship moves at $36\\,\\text{km/h}$. Its speed in $\\text{m/s}$ is:",
    options: ["$5$", "$10$", "$12$", "$36$"],
    answer: 1,
    explanation: "$$36\\,\\text{km/h} = 36 \\times \\frac{1000}{3600}\\,\\text{m/s} = 10\\,\\text{m/s}$$",
  },
  {
    id: 25,
    body: "One nautical mile is equal to:",
    options: ["$1609\\,\\text{m}$", "$1852\\,\\text{m}$", "$1000\\,\\text{m}$", "$1760\\,\\text{m}$"],
    answer: 1,
    explanation: "By international definition, $1$ nautical mile $= 1852\\,\\text{m}$ (one minute of latitude).",
  },
];

/** Option IDs are derived so they stay stable: question 3, option index 2 → 32. */
export const optionId = (questionId: number, index: number) => questionId * 10 + index + 1;
