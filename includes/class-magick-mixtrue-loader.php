<?php

/**
 * 注册插件的所有操作和筛选器
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 *注册插件的所有操作和筛选器。
 *
 *维护整个注册的所有钩子的列表
 *并使用WordPress API注册它们。致电
 *运行函数以执行操作和筛选器列表。
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class Magick_Mixtrue_Loader
{

    /**
     * 在WordPress中注册的操作数组。
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions   在插件加载时向WordPress注册以启动的操作。
     */
    protected $actions;

    /**
     * 在WordPress中注册的过滤器数组。
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    在WordPress中注册的过滤器在加载插件时启动。
     */
    protected $filters;

    /**
     * 初始化用于维护操作和筛选器的集合。
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        $this->actions = array();
        $this->filters = array();

    }

    /**
     * 将新操作添加到要注册到WordPress的集合。
     *
     * @since    1.0.0
     * @param    string               $hook            正在注册的WordPress操作的名称。
     * @param    object               $component        对定义了操作的对象实例的引用。
     * @param    string               $callback         $组件上函数定义的名称。
     * @param    int                  $priority         可选择的应激发函数的优先级。默认值为10。
     * @param    int                  $accepted_args    可选择的应传递给$callback的参数数。默认值为1。
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * 向要注册到WordPress的集合添加新筛选器。
     *
     * @since    1.0.0
     * @param    string               $hook             正在注册的WordPress筛选器的名称。
     * @param    object               $component        对定义过滤器的对象实例的引用。
     * @param    string               $callback         $组件上函数定义的名称。
     * @param    int                  $priority         可选择的应激发函数的优先级。默认值为10。
     * @param    int                  $accepted_args    可选择的应传递给$callback的参数数。默认值为1
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * 用于将操作和挂钩注册到单个
     *收集。
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks           正在注册的钩子的集合（即操作或筛选器）。
     * @param    string               $hook            正在注册的WordPress筛选器的名称。
     * @param    object               $component        对定义过滤器的对象实例的引用。
     * @param    string               $callback         $组件上函数定义的名称。
     * @param    int                  $priority         应激发函数的优先级。
     * @param    int                  $accepted_args    应传递给$callback的参数数。
     * @return   array                                  在WordPress中注册的操作和筛选器的集合。
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {

        $hooks[] = array(
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;

    }

    /**
     * 在WordPress中注册过滤器和操作。
     *
     * @since    1.0.0
     */
    public function run()
    {

        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        //循环获取数组或对象的值
        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

    }

}
