<td colspan="{{ $colspan }}">
    <div class="empty-state">
        <div class="empty-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>
        <p>{{ $message }}</p>
        @if (!empty($resetUrl))
            <a href="{{ $resetUrl }}" class="btn small">Reset Filter</a>
        @endif
    </div>
</td>