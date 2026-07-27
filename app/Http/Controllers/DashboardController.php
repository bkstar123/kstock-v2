<?php
/**
 * DashboardController - the CMS landing page.
 *
 * The watchlist and "comparable symbols" lists used to be built by inline PHP in
 * cms/dashboard.blade.php. The comparable list in particular read every admin's
 * statements with no admin_id filter, which contradicted the
 * financial.statements.show gate; it now goes through StatementLibrary::comparable(),
 * the single definition shared with ComparisonController.
 */
namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Services\StatementLibrary;

class DashboardController extends Controller
{
    public function index(StatementLibrary $library)
    {
        $me = auth()->guard('admins')->user();

        return view('cms.dashboard', [
            'watch' => Watchlist::with('symbol')
                ->where('admin_id', $me->id)
                ->orderBy('symbol_code')
                ->get(),
            'comparable' => $library->comparable($me),
        ]);
    }
}
