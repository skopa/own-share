<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 14.05.2018
 * Time: 21:02
 */

namespace App\Console\Commands;


use Illuminate\Console\Command;

class RenameOld extends Command
{
    protected $signature = "old:rename";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Import old files";


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $files = \App\Resource::all();

        $files->each(function (\App\Resource $item) {
            $file = storage_path('files/' . $item->identity . '_' . $item->name);
            $exist = file_exists($file);
            if ($exist) {
                $new = storage_path('files/' . $item->internal_identity);
                rename($file, $new);
            }
        });
    }

}