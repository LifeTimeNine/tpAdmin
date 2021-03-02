<?php

namespace tpadmin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Env;

/**
 * 安装指令
 * @class Install
 */
class Install extends Command
{
    protected function configure()
    {
        $this->setName('tpadmin:install')
            ->setDescription('安装 tpadmin');
    }
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始创建数据表...');
        $tableNum = $this->createTable();
        $output->writeln("数据表创建完成，共创建 {$tableNum} 个数据表。");

        $output->writeln('开始复制文件...');
        $file_num = $this->copyFile();
        $output->writeln("文件复制完成，共复制 {$file_num} 个文件。");

        $output->writeln("项目创建完成");
    }

    /**
     * 复制项目所需文件
     * @return  int 复制文件数量
     */
    private function copyFile()
    {
        // 资源目录
        $resourcesPath = Env::get('VENDOR_PATH') . '/lifetime/tpadmin/resources';
        // APP目录
        $appPath = Env::get('APP_PATH');
        // 静态资源目录
        $staticPath = Env::get('ROOT_PATH') . '/public/static';
        //记录拷贝文件数量
        $file_num = 0;
        // 拷贝静态资源
        $this->copyDir("{$resourcesPath}/static", $staticPath, $file_num);
        // 拷贝控制器资源
        $this->copyDir("{$resourcesPath}/application", $appPath, $file_num);
        return $file_num;
    }

    /**
     * 创建项目所需数据表
     * @return  int 创建的表数量
     */
    private function createTable()
    {
        // SQL资源目录
        $resourcesPath = Env::get('VENDOR_PATH') . '/lifetime/tpadmin/resources/sql';
        Db::execute('SET NAMES utf8mb4;');
        $tableNum = 0;
        foreach(scandir($resourcesPath) as $sqlFile) {
            if ($sqlFile == '.' || $sqlFile == '..') continue;
            $tableName = pathinfo($sqlFile, PATHINFO_FILENAME);
            Db::execute("DROP TABLE IF EXISTS `{$tableName}`;");
            $sqlStr = file_get_contents("{$resourcesPath}/{$sqlFile}");
            $sqlArr = preg_split('/--[\s-\n]+Records of.+\n[-\s]+/',$sqlStr);
            Db::execute($sqlArr[0]);
            if (isset($sqlArr[1])) {
                foreach(preg_split('/\n/', $sqlArr[1]) as $insertItem) {
                    if (empty($insertItem)) continue;
                    Db::execute($insertItem);
                }
            }
            $tableNum ++;
        }
        return $tableNum;
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
}