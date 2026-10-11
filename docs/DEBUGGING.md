# Debugging checklist
1. Reproduce. 2. Find route (`php artisan route:list`), controller, service. 3. Check `storage/logs/laravel.log` and the activity log (`request_id`). 4. Write a failing test. 5. Fix. 6. `php artisan test`.
