{{-- Cheat-sheet: what to type for common formulas. Click a code to copy it. --}}
@php
    $rows = [
        ['Fraction', '\frac{a}{b}', '$\frac{a+b}{c}$'],
        ['Square root / cube root', '\sqrt{x}   \sqrt[3]{x}', '$\sqrt{x^2+y^2}$  $\sqrt[3]{8}$'],
        ['Power / index', 'x^{2}   x_{1}   x_{n}^{2}', '$x^{2}$  $F_{1}$  $a_{n}^{2}$'],
        ['Degrees, Greek letters', '30^\circ   \theta   \alpha   \lambda   \pi', '$30^\circ$  $\theta$  $\alpha$  $\lambda$  $\pi$'],
        ['Compare', '\le   \ge   \ne   \approx', '$a \le b$  $a \ge b$  $a \ne b$  $a \approx b$'],
        ['Sets', '\in  \notin  \subset  \cup  \cap  \emptyset  \mathbb{R}  \mathbb{N}', '$x \in A$  $A \subset B$  $A \cup B$  $A \cap B$  $\emptyset$  $\mathbb{R}$'],
        ['Complement, bar, vector', 'A\'   \bar{A}   \vec{F}   \hat{i}', '$A\'$  $\bar{A}$  $\vec{F}$  $\hat{i}$'],
        ['Integral, sum, limit', '\int_0^1 x\,dx   \sum_{n=1}^{5} n   \lim_{x\to 0}', '$\int_0^1 x\,dx$  $\sum_{n=1}^{5} n$  $\lim_{x\to 0}\frac{\sin x}{x}$'],
        ['Chemistry', '\ce{H2O}   \ce{2H2 + O2 -> 2H2O}   \ce{SO4^2-}', '$\ce{H2O}$  $\ce{2H2 + O2 -> 2H2O}$  $\ce{SO4^2-}$'],
    ];
@endphp
<div>
    <p class="cb-muted" style="margin-bottom:.5rem">
        Put every formula between dollar signs: <code>$ ... $</code> inside a sentence, or <code>$$ ... $$</code> to show it on its own line. Click a code to copy it.
    </p>
    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
        <thead>
            <tr style="text-align:left;color:var(--gray-500)">
                <th style="padding:.375rem .5rem;width:22%">What</th>
                <th style="padding:.375rem .5rem;width:40%">Type this</th>
                <th style="padding:.375rem .5rem">You get</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as [$what, $code, $shown])
                <tr style="border-top:1px solid var(--gray-200);vertical-align:top">
                    <td style="padding:.5rem">{{ $what }}</td>
                    <td style="padding:.5rem">
                        <code x-data x-on:click="navigator.clipboard?.writeText($el.innerText)" style="cursor:copy;white-space:pre-wrap;word-break:break-word" title="Click to copy">{{ $code }}</code>
                    </td>
                    <td style="padding:.5rem" class="cb-math">{{ $shown }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
