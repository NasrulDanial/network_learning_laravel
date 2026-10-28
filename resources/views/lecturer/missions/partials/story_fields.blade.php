{{--
    Optional story ticket shown in Mission Run (boot ticket and debrief). Every field falls back
    to a sensible default when left empty. Expects $mission (null on create).
--}}
@php
    $fieldStyle = 'width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem; font-family: inherit;';
    $labelStyle = 'display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;';
    $hintStyle = 'display: block; font-size: 0.75rem; color: rgba(255, 255, 255, 0.5); margin-top: 6px;';
    $hasStory = old('ticket_code', $mission?->ticket_code) || old('briefing_text', $mission?->briefing_text)
        || old('debrief_text', $mission?->debrief_text) || old('narrator', $mission?->narrator);
@endphp

<details class="story-fields" style="margin-bottom: 22px; border: 1px solid rgba(34, 211, 238, 0.2); border-radius: 14px; padding: 14px 16px; background: rgba(7, 20, 38, 0.4);" {{ $hasStory || $errors->hasAny(['ticket_code', 'briefing_text', 'debrief_text', 'narrator']) ? 'open' : '' }}>
    <summary style="cursor: pointer; font-size: 0.88rem; font-weight: 700; color: #FFFFFF;">
        <i class="fa-solid fa-ticket" style="color: var(--nl-cyan, #22d3ee);" aria-hidden="true"></i>
        Story ticket <span style="font-weight: 500; color: rgba(255, 255, 255, 0.55);">(optional, shown in Mission Run)</span>
    </summary>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
        <div>
            <label for="ticket_code" style="{{ $labelStyle }}">Ticket code</label>
            <input type="text" id="ticket_code" name="ticket_code" maxlength="20" placeholder="#0142"
                   value="{{ old('ticket_code', $mission?->ticket_code) }}" aria-describedby="ticket_code_hint" style="{{ $fieldStyle }}">
            <small id="ticket_code_hint" style="{{ $hintStyle }}">Up to 20 characters. Defaults to the mission ID, e.g. #0007.</small>
        </div>
        <div>
            <label for="narrator" style="{{ $labelStyle }}">Narrator</label>
            <input type="text" id="narrator" name="narrator" maxlength="60" placeholder="Senior Network Engineer"
                   value="{{ old('narrator', $mission?->narrator) }}" aria-describedby="narrator_hint" style="{{ $fieldStyle }}">
            <small id="narrator_hint" style="{{ $hintStyle }}">Who briefs the student. Up to 60 characters.</small>
        </div>
    </div>

    <div style="margin-top: 16px;">
        <label for="briefing_text" style="{{ $labelStyle }}">Briefing</label>
        <textarea id="briefing_text" name="briefing_text" rows="3" maxlength="500" aria-describedby="briefing_text_hint"
                  placeholder="Lab Block B lost connectivity after a cabling change. Trace the fault layer by layer."
                  style="{{ $fieldStyle }} resize: vertical;">{{ old('briefing_text', $mission?->briefing_text) }}</textarea>
        <small id="briefing_text_hint" style="{{ $hintStyle }}">Typed out on the ticket before the first task. Up to 500 characters; defaults to the mission objective.</small>
    </div>

    <div style="margin-top: 16px;">
        <label for="debrief_text" style="{{ $labelStyle }}">Debrief</label>
        <textarea id="debrief_text" name="debrief_text" rows="2" maxlength="300" aria-describedby="debrief_text_hint"
                  placeholder="Link restored. Lab Block B is back online."
                  style="{{ $fieldStyle }} resize: vertical;">{{ old('debrief_text', $mission?->debrief_text) }}</textarea>
        <small id="debrief_text_hint" style="{{ $hintStyle }}">Shown when the ticket is closed. Up to 300 characters; defaults to “Ticket resolved. Network services restored.”</small>
    </div>
</details>
