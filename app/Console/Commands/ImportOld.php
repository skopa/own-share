<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 14.05.2018
 * Time: 21:02
 */

namespace App\Console\Commands;


use App\File;
use App\Resource;
use Illuminate\Console\Command;

class ImportOld extends Command
{
    protected $signature = "old:import";

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
        $files = File::all();

        $map = $files->map(function ($item) {
            $file = storage_path('files/' . $item->link . '_' . $item->name);

            $file_exist = file_exists($file);

            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'identity' => $item->link,
                'type_id' => 1,
                'internal_identity' => str_random() . '.' . $item->ext,
                'size' => $file_exist ? filesize($file) : $item->size,
                'reviews_count' => $item->downloads,

                'name' => $item->name,
                'is_public' => $item->is_public,
                'is_private' => $item->is_private,

                'deleted_at' => $file_exist ? null : $item->created_at
            ];
        });

        $map->each(function ($item) {
            Resource::updateOrCreate([
                'id' => $item['id']
            ], $item);
        });

        //echo $map->toJson();
    }

}