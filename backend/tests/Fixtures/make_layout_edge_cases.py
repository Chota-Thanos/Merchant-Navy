"""Builds layout-edge-cases.pdf: no real course content, only the page layouts that once broke the PDF reader.

  python make_layout_edge_cases.py            (needs: pip install pymupdf)

1. Pages 1-4 each START with a question whose option lines sit in the top margin. "(a) 4 (b) 8" has the same
   shape on every page, so it used to be thrown away as a running header (options A and B went missing).
2. Page 5 has stacked fractions in a two-column option grid. The numerator of the next row used to be pulled into
   the fraction above it ("x/x2", "(+5)/2"), and the fraction beside "(b)" used to end up in the question text.
"""
import fitz

doc = fitz.open()


def text(page, x, y, s, size=10, bold=False):
    page.insert_text((x, y), s, fontsize=size, fontname="hebo" if bold else "helv")


def frac(page, x, base, num, den, width):
    """A LaTeX-style text fraction: 7 pt numerator and denominator around a thin bar, like the real papers."""
    text(page, x + 0.3, base - 4.2, num, 7)
    text(page, x + 0.3, base + 5.2, den, 7)
    page.draw_line((x, base - 1.8), (x + width, base - 1.8), width=0.4)


for n in range(1, 5):
    p = doc.new_page()
    text(p, 45.4, 46, f"Q{n}. What is {n} + {n + 6}?")
    text(p, 45.4, 63, "(a) 4"); text(p, 303.6, 63, "(b) 8")
    text(p, 45.4, 77, "(c) 7"); text(p, 303.6, 77, "(d) 9")
    text(p, 45.4, 92, "Answer: (d) 9", bold=True)
    text(p, 45.4, 106, "Explanation", bold=True)
    text(p, 45.4, 120, "Add the two numbers.")
    for i in range(6):  # body text so the page has a normal amount of lines
        text(p, 45.4, 200 + 14 * i, "Filler line of ordinary text for the page body.")

p = doc.new_page()
text(p, 45.4, 100, "Q5. The inverse of the function f(x) = 2x + 5 is f(x) =")
b1, b2 = 118.0, 135.0
text(p, 45.4, b1, "(a)"); frac(p, 62.6, b1, "x", "2", 4.5); text(p, 70.5, b1, "+ 5")
text(p, 303.6, b1, "(b)"); frac(p, 321.4, b1, "1", "2x+5", 17.9)
text(p, 45.4, b2, "(c)"); frac(p, 62.0, b2, "x+5", "2", 14.0)
text(p, 303.6, b2, "(d)"); frac(p, 321.4, b2, "x+3", "2", 13.9)
text(p, 45.4, 158, "Answer: (d)", bold=True)
text(p, 45.4, 172, "Explanation", bold=True)
text(p, 45.4, 186, "Solve y = 2x + 5 for x.")

doc.save("layout-edge-cases.pdf")
print("saved layout-edge-cases.pdf")
