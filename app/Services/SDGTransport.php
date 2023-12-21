<?php

namespace App\Services;

use FtpClient\FtpClient;
use Illuminate\Support\Str;

class SDGTransport
{
    private string $ftp_login;
    private string $ftp_pass;
    private string $ftp_host;
    private string $ftp_dir;

    public FtpClient $ftp;
    public           $connect;

    public function __construct ()
    {
        $this->ftp_login = config('sdg.ftp.login');
        $this->ftp_pass = config('sdg.ftp.pass');
        $this->ftp_host = config('sdg.ftp.host');
        $this->ftp_dir = config('sdg.ftp.dir');

        $this->connect = ftp_connect($this->ftp_host);
        $this->ftp_login($this->ftp_login, $this->ftp_pass);
        $this->ftp_pasv(true);
        $this->ftp_raw('OPTS UTF8 ON');
    }

    public function __call ($func, $a)
    {
        if (str_contains($func, 'ftp_') && function_exists($func)) {
            array_unshift($a, $this->connect);
            return call_user_func_array($func, $a);
        } else {
            // replace with your own error handler.
            die("$func is not a valid FTP function");
        }
    }

    public function move (string $file, string $dir = 'Ok', string $error = null)
    {
        if ($this->ftp_size($file) === -1) return;
        $dir = $this->ftp_dir . '/' . $dir . '/' . date('Y/m/d');
        $tmp = pathinfo($file);
        $newfile = $tmp['filename'] . '_' . rand(1000, 9999) . '.' . $tmp['extension'];
        $this->ftp_mkdir_recursive($dir);
        if ($error) {
            $newfile = $tmp['filename'] . '_' . rand(1000, 9999) . '_' . Str::slug($error, '_') . '.' . $tmp['extension'];
        }
        $this->ftp_rename($file, $dir . '/' . $newfile);
    }

    public function ftp_mkdir_recursive (string $dir)
    {
        $parts = explode('/', $dir);
        foreach ($parts as $k => $part) {
            if ($k < 2) continue;
            if (!@$this->ftp_chdir($part)) {
                $this->ftp_mkdir($part);
                $this->ftp_chdir($part);
            }
        }
    }
}