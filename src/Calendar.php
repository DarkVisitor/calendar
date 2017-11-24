<?php

/**
 * TODO 日历显示
 * User: DarkVisitor
 * Date: 2017/11/22
 * Time: 13:48
 */
namespace DarkVisitor\Calendar;

include "Lunar.php";

class Calendar
{
    /**
     * 日历显示区间（年或月）
     */
    private $dateType;

    /**
     * 年
     */
    private $year;

    /**
     * 月
     */
    private $month;

    //返回数据
    private $result;

    //节假数据
    private $holidays = [
        '01-01' => ['Gregorian' => '元旦', 'Lunar' => '春节'],
        '01-15' => ['Gregorian' => '', 'Lunar' => '元宵节'],
        '02-14' => ['Gregorian' => '情人节', 'Lunar' => ''],
        '03-08' => ['Gregorian' => '妇女节', 'Lunar' => ''],
        '03-12' => ['Gregorian' => '植树节', 'Lunar' => ''],
        '04-04' => ['Gregorian' => '清明', 'Lunar' => ''],
        '05-01' => ['Gregorian' => '劳动节', 'Lunar' => ''],
        '05-04' => ['Gregorian' => '青年节', 'Lunar' => ''],
        '05-05' => ['Gregorian' => '', 'Lunar' => '端午节'],
        '05-14' => ['Gregorian' => '母亲节', 'Lunar' => ''],
        '06-01' => ['Gregorian' => '儿童节', 'Lunar' => ''],
        '06-18' => ['Gregorian' => '父亲节', 'Lunar' => ''],
        '07-01' => ['Gregorian' => '建党节', 'Lunar' => ''],
        '07-07' => ['Gregorian' => '', 'Lunar' => '七夕'],
        '08-01' => ['Gregorian' => '建军节', 'Lunar' => ''],
        '08-15' => ['Gregorian' => '', 'Lunar' => '中秋节'],
        '09-09' => ['Gregorian' => '', 'Lunar' => '重阳节'],
        '09-10' => ['Gregorian' => '教师节', 'Lunar' => ''],
        '10-01' => ['Gregorian' => '国庆节', 'Lunar' => '']
    ];

    public function __construct($config)
    {
        $this->dateType = $config['type'];
    }

    /**
     * 获取输入日期的日历数据
     * @param $date
     * @return array
     */
    public function getCalendar($date)
    {
        $this->setCurrentYearAndMonth($date);
        if ($this->dateType == 'year'){
            $this->result = $this->getOneYearDate();
        }elseif ($this->dateType == 'month'){
            $this->result = $this->getOneMonthData($this->month);
        }

        return $this->result;
    }


    /**
     * 设置当前年月
     * @param $date
     */
    private function setCurrentYearAndMonth($date)
    {
        $this->year = date('Y', strtotime($date));
        $this->month = date('m', strtotime($date));
    }

    /**
     * 获取一年的数据
     * @return array
     */
    private function getOneYearDate()
    {
        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];     //月份
        $yearArr = [];

        foreach ($months as $item){
            $yearArr[] = $this->getOneMonthData($item);
        }

        return $yearArr;
    }

    /**
     * 获取一个月的数据
     * @param $month    月份
     * @return array
     */
    public function getOneMonthData($month)
    {
        $monthArr = [];
        $week = [];
        $indexDay = 1;

        //获取每月第一天的星期
        $firstDayWeek = $this->getMonthlyFirstDayWeek($month);

        //获取当月的天数
        $currentMonthDays = $this->getCurrentMonthDays($month);

        //获取第一周剩余天数
        $oneWeekDays = 7 - $firstDayWeek;

        //获取从第二周开始的完整周期数
        $fullWeeks = intval(($currentMonthDays - $oneWeekDays) / 7);

        //获取最后剩余的天数
        $lastDays = $currentMonthDays - ($fullWeeks * 7) - $oneWeekDays;

        //获取第一周的数据
        for ($i=0; $i < 7; $i++){
            if ($i >= $firstDayWeek){
                $week[$i]['day'] = $indexDay;
                $week[$i]['holidays'] = $this->is_holidays($this->year.'-'.$month.'-'.$indexDay);
                $week[$i]['dateTime'] = date('Y-m-d', strtotime($this->year.'-'.$month.'-'.$indexDay));
                $indexDay++;
            }else{
                $week[$i] = ['day'=>'', 'holidays'=>'', 'dateTime'=>''];
            }
        }
        $monthArr[] = $week;

        //获取完整周期的数据
        for ($i=0; $i < $fullWeeks; $i++){
            for ($j=0; $j < 7; $j++){
                $week[$j]['day'] = $indexDay;
                $week[$j]['holidays'] = $this->is_holidays($this->year.'-'.$month.'-'.$indexDay);
                $week[$j]['dateTime'] = date('Y-m-d', strtotime($this->year.'-'.$month.'-'.$indexDay));
                $indexDay++;
            }
            $monthArr[] = $week;
        }

        //获取最后一周的数据
        if ($lastDays){
            for ($i=0; $i < 7; $i++){
                if ($i < $lastDays){
                    $week[$i]['day'] = $indexDay;
                    $week[$i]['holidays'] = $this->is_holidays($this->year.'-'.$month.'-'.$indexDay);
                    $week[$i]['dateTime'] = date('Y-m-d', strtotime($this->year.'-'.$month.'-'.$indexDay));
                    $indexDay++;
                }else{
                    $week[$i] = ['day'=>'', 'holidays'=>'', 'dateTime'=>''];
                }
            }
            $monthArr[] = $week;
        }
        unset($week);

        return $monthArr;
    }

    /**
     * 判断年份是否是闰年
     * @return bool
     */
    private function isLeapYear()
    {
        $year = $this->year;
        if (!is_numeric($year)){
            //throw 'year not number';
        }
        if (($year % 4 == 0 && $year % 100 != 0) || ($year % 100 == 0 && $year % 400 == 0)){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 获取每月第一天是星期几（星期天到星期六[0-6]）,注：星期天返回 0
     * @param $month    月份
     * @return false|string
     */
    public function getMonthlyFirstDayWeek($month)
    {
        return date('w', strtotime($this->year.'-'.$month.'-01'));
    }


    /**
     * 获取当月天数
     * @param $month    月份
     * @return false|string
     */
    public function getCurrentMonthDays($month)
    {
        return date('t', strtotime($this->year.'-'.$month.'-01'));
    }


    /**
     * 获取节假日
     * @param $date 日期
     * @return string
     */
    public function is_holidays($date)
    {
        $holidays = '';
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $days = date('d', strtotime($date));

        if (array_key_exists(date('m-d', strtotime($date)), $this->holidays)){
            $GregorianHolidays = $this->holidays[date('m-d', strtotime($date))]['Gregorian'];
        }

        //获取公历对应的农历
        $lunar = new \Lunar();
        $monthDay = $lunar->convertSolarToLunar($year, $month, $days);
        $monthDay = $this->chineseCharactersTurnNumber($monthDay[1]).'-'.($monthDay[5]<10?'0'.$monthDay[5]:$monthDay[5]);

        if (array_key_exists($monthDay, $this->holidays)){
            $LunarHolidays = $this->holidays[$monthDay]['Lunar'];
        }

        if (isset($GregorianHolidays) && !empty($GregorianHolidays)){
            $holidays = $GregorianHolidays;
        }elseif (isset($LunarHolidays) && !empty($LunarHolidays)){
            $holidays = $LunarHolidays;
        }

        return $holidays;
    }


    /**
     * 月份汉字转数字
     * @param $string
     * @return mixed
     */
    public function chineseCharactersTurnNumber($string)
    {
        $months = [
            '正月' => '01',
            '二月' => '02',
            '三月' => '03',
            '四月' => '04',
            '五月' => '05',
            '六月' => '06',
            '七月' => '07',
            '八月' => '08',
            '九月' => '09',
            '十月' => '10',
            '冬月' => '11',
            '腊月' => '12',
            '闰一月' => '01',
            '闰二月' => '02',
            '闰三月' => '03',
            '闰四月' => '04',
            '闰五月' => '05',
            '闰六月' => '06',
            '闰七月' => '07',
            '闰八月' => '08',
            '闰九月' => '09',
            '闰十月' => '10',
            '闰十一月' => '11',
            '闰十二月' => '12'
        ];

        return $months[$string];
    }
}