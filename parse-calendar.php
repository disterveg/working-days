<?
header('Content-type: text/html; charset=utf-8');
require_once 'phpQuery.php';

class WorkingDays
{
    private const URL_PARSE = 'https://hh.ru/calendar';
    private $file = '';

    /**
     * Парсим производственный календарь на hh.ru и возвращаем днм в формате d.m.Y
     *
     * @param string $selector
     * @return array
     */
    private function parseCalendar(string $selector) : array
    {
        $this->file = file_get_contents(self::URL_PARSE);
        $doc = phpQuery::newDocument($this->file);

        $days = [];
        foreach ($doc->find('.calendar-list__item') as $key => $month) {
            $month = pq($month);
            foreach ($month->find($selector) as $k => $dayOff) {
                $cntDay = substr(preg_replace("/[^0-9]/", '', pq($dayOff)->text()), 0, 2);
                $cntDay = strlen($cntDay) == 1 ? '0' . $cntDay : $cntDay;
                $cntMonth =  strlen(($key + 1)) == 1 ? '0' . ($key + 1) : ($key + 1);
                $cntYear = date('Y');
                $days[] = $cntDay . '.' . $cntMonth . '.' . $cntYear;
            }

        }

        return $days;
    }

    /**
     * Получить праздничные и выходные дни
     *
     * @return array
     */
    public function getDaysOff() : array
    {
        return $this->parseCalendar('.calendar-list__numbers__item_day-off');
    }

    /**
     * Получить выходные дни
     *
     * @return array
     */
    public function getWeekends() : array
    {
        $days = $this->parseCalendar('.calendar-list__numbers__item_day-off');
        $daysWeekends = [];
        foreach ($days as $day) {
            if((new DateTime($day))->format('N') == 6 || (new DateTime($day))->format('N') == 7) {
                $daysWeekends[] = $day;
            }
        }
        return $daysWeekends;
    }

    /**
     * Получить праздничные дни
     *
     * @return array
     */
    public function getHolidays() : array
    {
        $days = $this->parseCalendar('.calendar-list__numbers__item_day-off');
        $daysHolidays = [];
        foreach ($days as $day) {
            if((new DateTime($day))->format('N') != '6' && (new DateTime($day))->format('N') != '7') {
                $daysHolidays[] = $day;
            }
        }
        return $daysHolidays;
    }

    /**
     * Получить укороченные дни
     *
     * @return array
     */
    public function getShortenedDays() : array
    {
        return $this->parseCalendar('.calendar-list__numbers__item_shortened');
    }

    /**
     * Получить первый рабочий день
     *
     * @param object $day
     * @return object
     */
    private function getFirstWorkingDay(object $day) : object
    {
        $daysOff = $this->getDaysOff();
        $nextDay = $day->modify('+1day');
        if (in_array($day->format('d.m.Y'), $daysOff)) {
            $this->getFirstWorkingDay($nextDay);
        }
        return $nextDay;
    }

    /**
     * Получить рабочие дни, возвращаем массив временных объектов
     *
     * @param string $from
     * @param int $cntWorkingDays
     * @return array
     */
    public function getWorkingDays(string $from, int $cntWorkingDays) : array
    {
        $from = new DateTime($from);
        $i = 0;
        $workingDays = [];
        $workingDays[0] = $from;
        while ($i < $cntWorkingDays) {
            $workingDays[$i+1] = $this->getFirstWorkingDay(clone($workingDays[$i])); //fix change date
            $i++;
        }
        return $workingDays;
    }

    /**
     * Получить последний рабочий день, возвращаем временно объект
     *
     * @param string $from
     * @param int $cntWorkingDays
     * @return array
     */
    public function getLasWorkingDay($from, $cntWorkingDays)
    {
        return end($this->getWorkingDays($from, $cntWorkingDays));
    }
}


$workingDays = (new WorkingDays)->getWorkingDays('17.04.2020', 5);
new dBug($workingDays);







