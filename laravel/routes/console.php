<?php

use Illuminate\Support\Facades\Schedule;

// Scrape products info hourly (only for products that haven't been checked for more than an hour) and max 5000
Schedule::command('scrape:products')->hourly();

// Poll alerts every 5 minutes
Schedule::command('alerts:notify')->everyFiveMinutes();