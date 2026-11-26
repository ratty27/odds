<!-- Candidate past race history -->
<div class="modal fade" id="CandidateHistory" tabindex="-1" aria-labelledby="CandidateHistoryLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="CandidateHistoryLabel"></h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="candidate_history_empty" class="text-muted d-none">{{ __('odds.candidate_history_empty') }}</div>
        <div class="odds-game-table-wrap d-none" id="candidate_history_table_wrap">
          <table class="table table-striped table-bordered odds-game-table mb-0">
            <thead>
              <tr>
                <th class="text-center">{{ __('odds.game_name') }}</th>
                <th class="text-center col-3">{{ __('odds.candidate_ranking') }}</th>
              </tr>
            </thead>
            <tbody id="candidate_history_body"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
