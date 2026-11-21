<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function index()
    {
        return view('pages.admin.backup.index', [
            'title' => 'Backup & Restore Database'
        ]);
    }

    public function export()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                
                $createTable = DB::select('SHOW CREATE TABLE `' . $tableName . '`');
                $sql .= "-- Table structure for table `{$tableName}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= array_values((array)$createTable[0])[1] . ";\n\n";
                
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $sql .= "-- Dumping data for table `{$tableName}`\n";
                    $sql .= "INSERT INTO `{$tableName}` VALUES \n";
                    
                    $inserts = [];
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ((array)$row as $value) {
                            if (is_null($value)) {
                                $values[] = "NULL";
                            } else {
                                $values[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $inserts[] = "(" . implode(", ", $values) . ")";
                    }
                    $sql .= implode(",\n", $inserts) . ";\n\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $fileName = 'backup_db_' . date('Y-m-d_H-i-s') . '.sql';
            
            return response($sql)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
                
        } catch (\Exception $e) {
            Log::error('Backup Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengekspor database: ' . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimetypes:text/plain,application/sql,application/octet-stream'
        ]);

        try {
            $file = $request->file('backup_file');
            
            if ($file->getClientOriginalExtension() !== 'sql') {
                return back()->with('error', 'Format file harus .sql');
            }

            $sql = file_get_contents($file->getRealPath());
            
            // Execute the raw SQL
            DB::unprepared($sql);
            
            return back()->with('success', 'Database berhasil diimport dan direstore.');
            
        } catch (\Exception $e) {
            Log::error('Restore Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengimport database: ' . $e->getMessage());
        }
    }
}
