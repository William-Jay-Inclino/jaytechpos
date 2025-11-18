# JayTechPOS

This repository contains JayTech POS built with Laravel.

## Monthly Utang Interest Processing

A scheduled command processes monthly interest for customers who have utang (debt). The command computes interest per-customer and records each interest application as a `customer_transactions` record.

Commands:

- Run now (manual):

    php artisan utang:process-monthly-tracking

- Check if any customers still need processing this month:

    php artisan utang:check-monthly-tracking

- List the customer NAMES (or details) that require interest processing this month:

  php artisan utang:list-monthly-candidates
  php artisan utang:list-monthly-candidates --details
  php artisan utang:list-monthly-candidates --limit=10
  php artisan utang:list-monthly-candidates --details --limit=5

Scheduler:

- The scheduler is configured to run `utang:process-monthly-tracking` on the 1st day of every month at 00:01 Asia/Manila in `bootstrap/app.php`.

Activity / Audit:

- After each run, an audit activity entry is written to the activity log (Spatie activity log) with description `Monthly interest processing completed` and properties:
  - total_customers
  - processed
  - skipped_already_applied
  - skipped_zero_interest
  - created_transaction_ids

Notes:

- Each customer's processing is done inside a DB transaction for safety.
- A migration was added to index `transaction_date` on the `customer_transactions` table to improve performance for date queries used when checking whether monthly interest was already applied.

If you want additional CLI options (dry-run, causer/user override, force), I can add them.
