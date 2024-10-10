<?php

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Schedule::command('horizon:snapshot')->everyFiveMinutes();

Schedule::command('queue:prune-batches --hours=168')->daily();
Schedule::command('queue:prune-failed --hours=168')->daily();
