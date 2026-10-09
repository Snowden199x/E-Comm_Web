{{--
    One-line explanation of how the compliance score is calculated, shown wherever a score appears in a popup.
    Mirrors User::complianceScore() (100 minus 10 per violation, never below 0). Warnings are NOT counted today.
    If the formula changes on the server (see docs/design/2026-10-07-admin-uiux-backend-needs.md), edit this one
    sentence so every popup stays accurate.
--}}
<p class="text-xs leading-relaxed text-gray-500">
    Score = 100 − 10 for each violation, never below 0. Warnings do not lower it yet, and a score does not recover over time.
</p>