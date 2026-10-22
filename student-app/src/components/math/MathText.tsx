import katex from "katex";
import { cn } from "@/lib/utils";

/**
 * Renders admin-written HTML that contains LaTeX.
 *   Inline:  $x^2 + y^2$
 *   Block:   $$\int_0^1 x\,dx$$
 * Used for questions, options, explanations, notes and results.
 * (HTML is sanitised by the backend before it is stored.)
 */
const MATH = /\$\$([\s\S]+?)\$\$|\$([^$\n]+?)\$/g;

export function renderMath(html: string) {
  return html.replace(MATH, (_, block: string | undefined, inline: string | undefined) =>
    katex.renderToString((block ?? inline)!, {
      displayMode: block !== undefined,
      throwOnError: false,
      strict: "ignore",
    }),
  );
}

export function MathText({ html, className, as: Tag = "div" }: { html: string; className?: string; as?: "div" | "span" }) {
  return <Tag className={cn("rich-text", className)} dangerouslySetInnerHTML={{ __html: renderMath(html) }} />;
}
