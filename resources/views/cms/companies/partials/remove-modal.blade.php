{{--
 | Remove-from-directory confirmation.
 |
 | The markup deliberately mirrors CrudView::destroyModal()
 | (packages/bkscms-utilities/src/Helpers/CrudViewHelper.php:239) so the existing
 | theme rules in public/cms-assets/css/modern.css:263-287 style it with no CSS
 | change and no ?v= bump. Keep the class list in sync with that helper.
 |
 | Opened purely by jQuery .modal('show') — there is no data-toggle/data-target,
 | same as every other modal in this app.
 |
 | The copy is intentionally NOT CrudView's "All related data will irreversibly be
 | removed": removing from the directory keeps the actor's watchlist entry and
 | financial statements, and only purges the shared catalog row if nothing else
 | references it.
 |
 | Expects: $symbol (App\Models\Symbol)
 --}}
<div class="modal fade" id="removing-modal-{{ $symbol->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h4 class="modal-title">Confirmation - remove from directory</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Remove <strong>{{ $symbol->code }}</strong> from <strong>your</strong> directory?</p>
                <i>
                    Only your own directory is affected — your watchlist entry and your financial
                    statements for {{ $symbol->code }} are kept, and no other admin's list changes.
                    {{ $symbol->code }} is purged from the shared catalog only if nothing else
                    references it any more.
                </i>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-light btn-danger"
                        onclick="event.preventDefault();$('#deleting-form-{{ $symbol->id }}').submit()">
                    Remove
                </button>
            </div>
        </div>
    </div>
</div>
