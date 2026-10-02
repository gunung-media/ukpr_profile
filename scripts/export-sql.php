<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if(config('database.connections.mysql.database')!=='ukpr_export')throw new RuntimeException('Ekspor hanya dari database bersih ukpr_export.');
if(DB::table('users')->exists())throw new RuntimeException('SQL produksi harus tanpa akun.');
$pdo=DB::connection()->getPdo();$tables=DB::select('SHOW TABLES');
$sql="-- UKPR profile: MySQL / MariaDB, utf8mb4. Import into a NEW EMPTY database.\n-- No admin accounts, secrets, sessions or local test data.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
foreach($tables as $tableRow){
    $table=array_values((array)$tableRow)[0]; if(!preg_match('/^[a-z_]+$/',$table))throw new RuntimeException('Unsafe table');
    $create=DB::select("SHOW CREATE TABLE `$table`")[0];$definition=array_values((array)$create)[1];$sql.=$definition.";\n";
    foreach(DB::table($table)->get() as $row){
        $values=(array)$row;$columns=implode(',',array_map(fn($column)=>'`'.$column.'`',array_keys($values)));
        $quoted=implode(',',array_map(fn($value)=>$value===null?'NULL':$pdo->quote((string)$value),array_values($values)));
        $sql.="INSERT INTO `$table` ($columns) VALUES ($quoted);\n";
    }
    $sql.="\n";
}
$sql.="SET FOREIGN_KEY_CHECKS=1;\n";
if(!is_dir(__DIR__.'/../dist'))mkdir(__DIR__.'/../dist',0755,true);
file_put_contents(__DIR__.'/../dist/ukpr.sql',$sql);echo 'SQL tanpa akun: '.strlen($sql)." bytes\n";
