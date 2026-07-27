<?php
/**
 * PullFinancialStatement Job
 *
 * @author: tuanha
 * @date: 28-July-2022
 */
namespace App\Jobs;

use RuntimeException;
use Throwable;
use App\Events\JobFailing;
use Illuminate\Bus\Queueable;
use App\Models\FinancialStatement;
use App\Models\IncomeStatement;
use App\Models\BalanceStatement;
use App\Models\CashFlowStatement;
use App\Services\Contracts\Symbols;
use App\Services\StatementLibrary;
use Illuminate\Queue\SerializesModels;
use App\Jobs\AnalyzeFinancialStatement;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\PullFinancialStatementCompleted;

class PullFinancialStatement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    protected $symbol;
    
    /**
     * @var integer
     */
    protected $year;
    
    /**
     * @var integer
     */
    protected $quarter;
    
    /**
     * @var \Bkstar123\BksCMS\AdminPanel\Admin
     */
    protected $user;

    /**
     * @var integer
     */
    protected $financialStatementID;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $financialStatementID, $user)
    {
        $this->symbol = trim($data['symbol']);
        $this->year = trim($data['year']);
        $this->quarter = trim($data['quarter']);
        $this->financialStatementID = $financialStatementID;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    /**
     * One attempt only.
     *
     * The job makes 3-4 UNCACHED upstream HTTP calls; retrying would re-fetch all of
     * them. The real protection against a double run is that the children are written
     * with updateOrCreate against a unique(financial_statement_id) — this is defence
     * in depth.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Must stay BELOW the connection's retry_after (config/queue.php), or the queue
     * reserves the job a second time while the first copy is still fetching and runs
     * it twice. That is how duplicate child rows were created in the first place.
     *
     * @var int
     */
    public $timeout = 540;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $symbols = resolve(Symbols::class);
        $limit = (int) config('settings.limits', 5) + 5;
        $written = 0;

        // updateOrCreate, not create: statements are shared, so a re-pull REFRESHES
        // the row in place rather than appending a second child. The content is a
        // window snapshot ending at the requested period, so re-pulling months later
        // legitimately yields restated data — and the unique index on
        // financial_statement_id makes this the only possible shape.
        $balanceStatement = $symbols->getFullFinancialStatement($this->symbol, 1, $this->year, $this->quarter, $limit);
        if ($this->usable($balanceStatement)) {
            BalanceStatement::updateOrCreate(
                ['financial_statement_id' => $this->financialStatementID],
                ['content' => $balanceStatement]
            );
            $written++;
        }

        $incomeStatement = $symbols->getFullFinancialStatement($this->symbol, 2, $this->year, $this->quarter, $limit);
        if ($this->usable($incomeStatement)) {
            IncomeStatement::updateOrCreate(
                ['financial_statement_id' => $this->financialStatementID],
                ['content' => $incomeStatement]
            );
            $written++;
        }

        $cashFlowStatementType = '';
        $cashFlowStatement = $symbols->getFullFinancialStatement($this->symbol, 3, $this->year, $this->quarter, $limit);
        if ($this->usable($cashFlowStatement)) {
            $cashFlowStatementType = 'direct';
        } else {
            $cashFlowStatement = $symbols->getFullFinancialStatement($this->symbol, 4, $this->year, $this->quarter, $limit);
            $cashFlowStatementType = $this->usable($cashFlowStatement) ? 'indirect' : '';
        }
        if ($cashFlowStatementType !== '') {
            CashFlowStatement::updateOrCreate(
                ['financial_statement_id' => $this->financialStatementID],
                ['content' => $cashFlowStatement]
            );
            $written++;
        }

        if ($written === 0) {
            // Nothing usable came back. The old code left a childless parent row
            // behind forever on every failure; drop it instead.
            $this->discardIfEmpty();
            throw new RuntimeException(
                "No usable financial statement data for {$this->symbol} {$this->year}Q{$this->quarter}"
            );
        }

        // "Last refreshed at", now that a re-pull overwrites in place. Bumped here
        // rather than in the controller so it means refreshed, not requested.
        FinancialStatement::whereKey($this->financialStatementID)->update(['updated_at' => now()]);

        // Was unconditional: analysis used to fire even when zero statements were
        // written, burning a getFundamentals call and running every calculator
        // against null relations.
        AnalyzeFinancialStatement::dispatch($this->financialStatementID, $this->user, $cashFlowStatementType);
        PullFinancialStatementCompleted::dispatch($this->user);
    }

    /**
     * The job failed to process.
     *
     * Must accept Throwable (not Exception): the job can fail with an Error, and a
     * narrower Exception hint would itself raise a TypeError here, masking the
     * real failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $this->discardIfEmpty();
        JobFailing::dispatch($this->user, $exception->getMessage());
    }

    /**
     * Is this payload present and actually the period we asked for?
     *
     * @param  string|false|null  $content
     */
    protected function usable($content): bool
    {
        return !empty($content) && $content !== 'null' && $this->validateStatement($content);
    }

    /**
     * Drop a statement that carries no data at all.
     *
     * The "no data at all" test is what makes this correct under refresh-in-place:
     *   - brand-new row, pull produced nothing  -> zero children -> purged. Right.
     *   - existing row, REFRESH produced nothing -> updateOrCreate never ran, so the
     *     old children are untouched -> not purged. Right: a failed refresh must
     *     never destroy data another admin is still using.
     */
    protected function discardIfEmpty(): void
    {
        try {
            $id = $this->financialStatementID;
            $hasData = BalanceStatement::where('financial_statement_id', $id)->exists()
                || IncomeStatement::where('financial_statement_id', $id)->exists()
                || CashFlowStatement::where('financial_statement_id', $id)->exists();

            if (!$hasData) {
                resolve(StatementLibrary::class)->purge($id);
            }
        } catch (Throwable $e) {
            // Cleanup must never mask the original failure.
            report($e);
        }
    }

    /**
     * Validate whether or not the pulled contents are of the desired financial statement
     *
     * @param string $content
     * @return boolean
     */
    protected function validateStatement($content)
    {
        // Throwable, not Exception: an empty payload makes array_first() return null,
        // and Arr::where(null, ...) raises a TypeError — an Error, which a narrower
        // catch would let escape.
        try {
            $firstItem = array_first(json_decode($content, true));
            $data = \Arr::where($firstItem['values'], function ($value) {
                return $value['year'] == $this->year && $value['quarter'] == $this->quarter;
            });
            return !empty($data);
        } catch (Throwable $e) {
            return false;
        }
    }
}
