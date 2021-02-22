<?php

namespace tpadmin\driver\page;

use think\Paginator;

/**
 * 分页驱动
 * 基于layui样式
 */
class Page extends Paginator
{
    /**
     * 生成一个启用的翻页按钮容器
     * @param   string  $url    路径
     * @param   string  $text   显示的内容
     * @return  string
     */
    protected function getEnableTurnPageWrapper($url, $text)
    {
        return "<a href=\"javascript:void(0);\" onclick=\"$.form.href('{$url}')\"><i class=\"layui-icon\">{$text}</i></a>";
    }

    /**
     * 生成一个禁用的翻页按钮
     * @param   string  $text   显示内容
     * @return  string
     */
    public function getDisabledTurnPageWrapper($text)
    {
        return "<a onclick=\"javascript:;\" class=\"layui-disabled\"><i class=\"layui-icon\">{$text}</i></a>";
    }

    /**
     * 生成一个当前页的容器
     * @param   string  $text
     * @return  string
     */
    protected function getActivePageWrapper($text)
    {
        return "<span class=\"layui-laypage-curr\"><em class=\"layui-laypage-em\"></em><em>{$text}</em></span>";
    }

    /**
     * 生成一个普通页码容器
     * @param   string  $url
     * @param   int     $page
     * @return  string
     */
    protected function getPageWrapper($url, $page)
    {
        return "<a href=\"javascript:void(0);\" onclick=\"$.form.href('{$url}')\">{$page}</a>";
    }

    /**
     * 生成一个首页容器
     * @return  string
     */
    protected function getFirstPageWrapper()
    {
        return "<a href=\"javascript:void(0);\" onclick=\"$.form.href('{$this->buildUrl(1)}')\" class=\"layui-laypage-first\" title=\"首页\">1</a>" . $this->getEllipsis();
    }

    /**
     * 生成一个尾页容器
     * @return  string
     */
    protected function getLastPageWrapper()
    {
        return $this->getEllipsis() . "<a href=\"javascript:void(0);\" onclick=\"$.form.href('{$this->buildUrl($this->lastPage())}')\" class=\"layui-laypage-last\" title=\"尾页\">{$this->lastPage()}</a>";
    }

    /**
     * 生成省略号
     */
    protected function getEllipsis()
    {
        return "<span class=\"layui-laypage-spr\">…</span>";
    }

    /**
     * 生成合计
     * @return  string
     */
    protected function getCount()
    {
        return "<span class=\"layui-laypage-count\">共 {$this->total()} 条</span>";
    }

    /**
     * 生成条数选择器
     * @return  string
     */
    protected function getLimitSelect()
    {
        $html = "<span class=\"layui-laypage-limits\"><select lay-ignore onchange=\"$.form.href(this.options[this.selectedIndex].value)\">";
        foreach (range(10, 100, 10) as $v) {
            $html .= "<option value=\"{$this->buildUrl(1, $v)}\" " . ($this->listRows() == $v ? 'selected' : '') . ">{$v} 条/页</option>";
        }
        $html .= "</select></span>";
        return $html;
    }

    /**
     * 根据页码和参数生成连接
     * @param   int     $page       分页索引
     * @param   int     $listRows   每页数量
     * @returh  string
     */
    protected function buildUrl($page = null, $listRows = null)
    {
        if (empty($page)) $page = $this->currentPage();
        if (empty($listRows)) $listRows = $this->listRows();
        return "{$this->url($page)}&listRows={$listRows}";
    }

    /**
     * 批量生成页码按钮
     * @param   array   $urls
     * @return  string
     */
    protected function getBatchPageWrapper($urls)
    {
        $html = '';
        foreach ($urls as $page => $url) {
            if ($this->currentPage() == $page) {
                $html .= $this->getActivePageWrapper($page);
            } else {
                $html .= $this->getPageWrapper("{$url}&listRows={$this->listRows()}", $page);
            }
        }
        return $html;
    }

    /**
     * 生成上一页按钮
     * @param   string  $text
     * @return  string
     */
    protected function getPreviousButton($text = "&#xe603;")
    {
        if ($this->currentPage() <= 1) {
            return $this->getDisabledTurnPageWrapper($text);
        }
        $url = $this->buildUrl($this->currentPage() - 1);
        return $this->getEnableTurnPageWrapper($url, $text);
    }

    /**
     * 生成下一页按钮
     * @param   string  $text
     * @return  string
     */
    protected function getNextButton($text = "&#xe602;")
    {
        if (!$this->hasMore) {
            return $this->getDisabledTurnPageWrapper($text);
        }
        $url = $this->buildUrl($this->currentPage() + 1);
        return $this->getEnableTurnPageWrapper($url, $text);
    }

    /**
     * 生成页码按钮
     * @return  string
     */
    protected function getPageButton()
    {
        $html = '';
        if ($this->currentPage() < 4) {
            if ($this->lastPage() <= 4) {
                $html .= $this->getBatchPageWrapper($this->getUrlRange(1, $this->lastPage()));
            } else {
                $html .= $this->getBatchPageWrapper($this->getUrlRange(1, 4));
                $html .= $this->getLastPageWrapper();
            }
        } else {
            $html .= $this->getFirstPageWrapper();
            if ($this->lastPage() > $this->currentPage() + 2) {
                $html .= $this->getBatchPageWrapper($this->getUrlRange($this->currentPage() - 1, $this->currentPage() + 1));
                $html .= $this->getLastPageWrapper();
            } else {
                $html .= $this->getBatchPageWrapper($this->getUrlRange($this->currentPage() - 1, $this->lastPage()));
            }
        }
        return $html;
    }

    /**
     * 渲染分页html
     * @return  string
     */
    public function render()
    {
        if ($this->hasPages()) {
            
            if ($this->simple) {
                $box = "<div class=\"layui-table-page\"><div class=\"layui-box layui-laypage layui-laypage-default\">%s%s<div></div></div>";
                return sprintf($box, $this->getPreviousButton(), $this->getNextButton());
            } else {
                $box = "<div class=\"layui-table-page\"><div class=\"layui-box layui-laypage layui-laypage-default\">%s%s%s%s%s<div></div></div>";
                return sprintf(
                    $box,
                    $this->getPreviousButton(),
                    $this->getPageButton(),
                    $this->getNextButton(),
                    $this->getCount(),
                    $this->getLimitSelect()
                );
            }
        }
    }
}
