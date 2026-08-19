<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Recurring Journal Reports</h1>
                    <div class="sub">Analytics and summaries for your recurring journal automation.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.rj.dashboard') }}" class="btn btn-ghost btn-sm">← Back to Dashboard</a>
                </div>
            </div>

            <div class="repcards">
                <div class="repcard">
                    <span class="t">Recurring Journal Summary</span>
                    <span class="d">All templates with status, frequency and totals.</span>
                    <div class="foot">
                        <span class="fmt">PDF</span>
                        <span class="fmt">Excel</span>
                        <a class="open-l" href="{{ route('accounting.rj.index') }}">Open →</a>
                    </div>
                </div>

                <div class="repcard">
                    <span class="t">Scheduled Journal Report</span>
                    <span class="d">Upcoming journal runs with amounts and schedules.</span>
                    <div class="foot">
                        <span class="fmt">PDF</span>
                        <span class="fmt">Excel</span>
                        <a class="open-l" href="{{ route('accounting.rj.scheduled') }}">Open →</a>
                    </div>
                </div>

                <div class="repcard">
                    <span class="t">Generated Journal Report</span>
                    <span class="d">All generated entries with posting status.</span>
                    <div class="foot">
                        <span class="fmt">PDF</span>
                        <span class="fmt">Excel</span>
                        <a class="open-l" href="{{ route('accounting.rj.generated') }}">Open →</a>
                    </div>
                </div>

                <div class="repcard">
                    <span class="t">Journal Posting History</span>
                    <span class="d">Historical posting record with dates and amounts.</span>
                    <div class="foot">
                        <span class="fmt">PDF</span>
                        <span class="fmt">Excel</span>
                        <a class="open-l" href="{{ route('accounting.rj.history') }}">Open →</a>
                    </div>
                </div>

                <div class="repcard">
                    <span class="t">Failed Journal Runs</span>
                    <span class="d">Generation failures with reasons and retry counts.</span>
                    <div class="foot">
                        <span class="fmt">PDF</span>
                        <span class="fmt">Excel</span>
                        <a class="open-l" href="{{ route('accounting.rj.generated', ['status' => 'failed']) }}">Open →</a>
                    </div>
                </div>

                <div class="repcard">
                    <span class="t">Expired-Upcoming Control</span>
                    <span class="d">Overview of expired and upcoming schedules.</span>
                    <div class="foot">
                        <span class="fmt">PDF</span>
                        <span class="fmt">Excel</span>
                        <a class="open-l" href="{{ route('accounting.rj.index') }}">Open →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
