<?php


use Illuminate\Support\Facades\Schedule;

//Schedule::command('app:consolidate-records')->weekly();
//Schedule::command('add-index')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
// Schedule::command('app:consolidate-records-cron')->everyMinute();