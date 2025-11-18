<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixPostgresSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fix-sequences {--table= : Specific table to fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix PostgreSQL sequence values to match the maximum ID in each table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('database.default') !== 'pgsql') {
            $this->error('This command only works with PostgreSQL databases.');

            return 1;
        }

        $specificTable = $this->option('table');

        if ($specificTable) {
            $this->fixSequenceForTable($specificTable);
        } else {
            $this->fixAllSequences();
        }

        return 0;
    }

    /**
     * Fix sequence for a specific table.
     */
    private function fixSequenceForTable(string $table): void
    {
        try {
            $sequenceName = $table.'_id_seq';

            // Check if sequence exists
            $sequenceExists = \DB::select('SELECT 1 FROM information_schema.sequences WHERE sequence_name = ?', [$sequenceName]);

            if (empty($sequenceExists)) {
                $this->warn("Sequence {$sequenceName} does not exist.");

                return;
            }

            // Get max ID from table
            $maxId = \DB::table($table)->max('id') ?? 0;

            // Update sequence
            \DB::statement("SELECT setval('{$sequenceName}', GREATEST({$maxId}, 1))");

            $this->info("Fixed sequence for table '{$table}' - set to {$maxId}");

        } catch (\Exception $e) {
            $this->error("Error fixing sequence for table '{$table}': ".$e->getMessage());
        }
    }

    /**
     * Fix sequences for all tables with auto-incrementing IDs.
     */
    private function fixAllSequences(): void
    {
        $tables = [
            'users',
            'units',
            'products',
            'customers',
            'sales',
            'sales_items',
            'expenses',
            'expense_categories',
            'customer_transactions',
        ];

        $this->info('Fixing PostgreSQL sequences for all tables...');

        foreach ($tables as $table) {
            $this->fixSequenceForTable($table);
        }

        $this->info('Sequence fixing completed!');
    }
}
