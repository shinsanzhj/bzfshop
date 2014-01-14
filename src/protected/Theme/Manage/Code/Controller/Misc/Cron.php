<?php

/**
 * @author QiangYu
 *
 *  Cron 定时任务列表
 *
 * */

namespace Controller\Misc;

use Core\Helper\Utility\QueryBuilder;
use Core\Helper\Utility\Route as RouteHelper;
use Core\Helper\Utility\Time;
use Core\Helper\Utility\Validator;
use Core\Service\Cron\Task as CronTaskService;

class Cron extends \Controller\AuthController
{

    public function get($f3)
    {
        global $smarty;

        // 权限检查
        $this->requirePrivilege('manage_misc_cron');

        // 参数验证
        $validator = new Validator($f3->get('GET'));
        $pageNo    = $validator->digits()->min(0)->validate('pageNo');
        $pageSize  = $validator->digits()->min(0)->validate('pageSize');

        // 设置缺省值
        $pageNo   = (isset($pageNo) && $pageNo > 0) ? $pageNo : 0;
        $pageSize = (isset($pageSize) && $pageSize > 0) ? $pageSize : 10;

        //查询条件
        $searchFormQuery              = array();
        $searchFormQuery['task_name'] = $validator->validate('task_name');
        $searchFormQuery['task_desc'] = $validator->validate('task_desc');

        $returnCode = $validator->digits()->filter('ValidatorIntValue')->validate('return_code');
        if (0 === $returnCode) {
            $searchFormQuery['return_code'] = 0;
        } else {
            $searchFormQuery['return_code'] = array('<>', 0);
        }

        //任务时间
        $taskTimeStartStr             = $validator->validate('task_time_start');
        $taskTimeStart                = Time::gmStrToTime($taskTimeStartStr) ? : null;
        $taskTimeEndStr               = $validator->validate('task_time_end');
        $taskTimeEnd                  = Time::gmStrToTime($taskTimeEndStr) ? : null;
        $searchFormQuery['task_time'] = array($taskTimeStart, $taskTimeEnd);

        if (!$this->validate($validator)) {
            goto out_display;
        }

        // 建立查询条件
        $searchParamArray = QueryBuilder::buildQueryCondArray($searchFormQuery);

        $cronTaskService = new CronTaskService();
        $totalCount      = $cronTaskService->countCronTaskArray($searchParamArray);

        if ($totalCount <= 0) { // 没任务，可以直接退出了
            goto out_display;
        }

        // 页数超过最大值，返回第一页
        if ($pageNo * $pageSize >= $totalCount) {
            RouteHelper::reRoute($this, '/Misc/Cron');
        }

        $cronTaskArray = $cronTaskService->fetchCronTaskArray($searchParamArray, $pageNo * $pageSize, $pageSize);

        $smarty->assign('cronTaskArray', $cronTaskArray);

        out_display:
        $smarty->display('misc_cron.tpl');
    }

}