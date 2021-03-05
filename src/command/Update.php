<?php

namespace tpadmin\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Env;

/**
 * 更新指令
 * @class   Update
 */
class Update extends Command
{
    protected function configure()
    {
        $this->setName('tpadmin:update')
            ->setDescription('更新 tpadmin （更新有风险，请勿跨大版本更新）')
            ->addOption('c', 'c', Option::VALUE_NONE, '只更新控制器文件')
            ->addOption('s', 's', Option::VALUE_NONE, '只更新静态资源文件')
            ->setHelp('查看帮助信息');
    }
    protected function execute(Input $input, Output $output)
    {
        // 资源目录
        $resourcesPath = Env::get('VENDOR_PATH') . '/lifetime/tpadmin/resources';
        // APP目录
        $appPath = Env::get('APP_PATH');
        // 静态资源目录
        $staticPath = Env::get('ROOT_PATH') . '/public/static';

        if ($input->hasOption('c')) {
            //记录拷贝文件数量
            $file_num = 0;
            // $output->writeln('开始删除控制器文件...');
            // $this->removeDir("{$appPath}/admin");
            // $output->writeln('控制器文件删除完成...');
            $output->writeln('开始复制控制器文件...');
            $this->copyDir("{$resourcesPath}/application", $appPath, $file_num);
            $output->writeln("文件复制完成，共复制 {$file_num} 个文件。");
        }
        if ($input->hasOption('s')) {
            //记录拷贝文件数量
            $file_num = 0;
            $output->writeln('开始复制静态资源文件...');
            $this->copyDir("{$resourcesPath}/static", $staticPath, $file_num);
            $output->writeln("文件复制完成，共复制 {$file_num} 个文件。");
        }

        if (!$input->hasOption('c') && !$input->hasOption('s')) {
            //记录拷贝文件数量
            $file_num = 0;
            $output->writeln('开始删除控制器文件...');
            $this->removeDir("{$appPath}/admin");
            $output->writeln('控制器文件删除完成...');
            $output->writeln('开始复制控制器文件...');
            $this->copyDir("{$resourcesPath}/application", $appPath, $file_num);
            $output->writeln("文件复制完成，共复制 {$file_num} 个文件。");
            //记录拷贝文件数量
            $file_num = 0;
            $output->writeln('开始复制静态资源文件...');
            $this->copyDir("{$resourcesPath}/static", $staticPath, $file_num);
            $output->writeln("文件复制完成，共复制 {$file_num} 个文件。");
        }
    }

    /**
     * 拷贝文件夹
     * @param   string  $source_dir     来源文件夹
     * @param   string  $target_dir     目标文件夹
     * @param   int     $file_num       文件数量
     */
    private function copyDir($source_dir, $target_dir, &$file_num = 0)
    {
        if (!is_dir($source_dir)) return false;
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        foreach(scandir($source_dir) as $value) {
            if ($value == '.' || $value == '..') continue;
            if (is_file("{$source_dir}/{$value}")) {
                $file_num ++;
                @copy("{$source_dir}/{$value}", "{$target_dir}/{$value}");
            } else {
                $this->copyDir("{$source_dir}/{$value}", "{$target_dir}/{$value}", $file_num);
            }
        }
        return true;
    }

    /**
     * 删除文件夹
     * @param   string  $dir    文件夹路径
     */
    private function removeDir($dir) {
        if (!is_dir($dir)) return true;
        foreach(scandir($dir) as $value) {
            if ($value == '.' || $value == '..') continue;
            if (is_file("{$dir}/{$value}")) {
                $url = iconv('utf-8', 'gbk', "{$dir}/{$value}");
                if(PATH_SEPARATOR == ':') {
                    @unlink("{$dir}/{$value}");
                } else {
                    @unlink($url);
                }
            } else {
                $this->removeDir("{$dir}/{$value}");
            }
        }
        @rmdir($dir);
        return true;
    }
}