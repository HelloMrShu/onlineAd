
<?php

$supply = [
    ['pv' => 400, 'location' => '', 'sex' => 'male', 'age' => 5],
    ['pv' => 400, 'location' => 'WA', 'sex' => 'male', 'age' => 5],
    ['pv' => 100, 'location' => 'CA', 'sex' => 'male', 'age' => ''],
    ['pv' => 100, 'location' => 'CA', 'sex' => '', 'age' => 5],
    ['pv' => 200, 'location' => 'NY', 'sex' => '', 'age' => 5],
//    ['pv' => 300, 'location' => '', 'sex' => '', 'age' => 5],
];

$demand = [
    [
        'pv' => 200,
        'target' => [
            'sex' => ['male'],
            'location' => [],
            'age' => [5]
        ]
    ],
    [
        'pv' => 500,
        'target' => [
            'location' => ['CA'],
            'sex' => [],
            'age' => []
        ]
    ],
    [
        'pv' => 1000,
        'target' => [
            'sex' => [],
            'location' => [],
            'age' => [5]
        ]
    ],
    [
        'pv' => 500,
        'target' => [
            'sex' => [],
            'location' => [],
            'age' => []
        ]
    ],
];

class Amount
{
    private $supply = []; //供给节点
    private $demand = []; //需求节点

    public function setData($supply, $demand)
    {
        $this->supply = $supply;
        $this->demand = $demand;
    }

    //检查定向是否匹配
    private function checkTargetMatch($dNode, $sNode): bool
    {
        foreach ($dNode as $dk => $dv) {                //dNode (sex => ['male'])
            $sv = explode(',', $sNode[$dk]);    //'location' => ['location']
            if ($sv && $dv && array_diff($dv, $sv)) {   //dv sv 做diff
                return false;
            }
        }

        return true;
    }

    //需求节点的可用库存
    private function getAvailableStock($target): int
    {
        $stock = 0;
        foreach ($this->supply as $supply) {
            $pv = $supply['pv'];
            if ($this->checkTargetMatch($target, $supply)) {
                $stock += $pv;
            }
        }

        return $stock;
    }

    //需求节点供给排序
    private function rankingByStock(): array
    {
        $stock = [];
        foreach ($this->demand as $k => $d) {
            $stock[$k] = $this->getAvailableStock($d['target']);
        }

        asort($stock);

        return $stock;
    }

    private function computeRate($pv, $total)
    {
        $rate = $total ? round($pv / $total, 13) : 0;

        return min($rate, 1);
    }

    //分配
    public function allocate(): array
    {
        echo "\n======================\n";
        $ranking = $this->rankingByStock();

        $supply = $this->supply;
        foreach ($supply as &$spy) {
            $spy['remain'] = $spy['pv'];
        }

        $result = [];
        foreach ($ranking as $k => $total) {
            $dm = $this->demand[$k];    // the Kth demand node

            echo ("The $k demand node\n");
            echo (json_encode($dm)). "\n";
            echo ("total: $total\n");
            $target = $dm['target'];    //demand target

            //compute rate
            $rate = $this->computeRate($dm['pv'], $total);
            echo ("rate: $rate\n");

            $match = [];
            foreach ($supply as $idx => $spyItem) {
                if (empty($spyItem['remain'])) {    //skip the node with zero remain
                    continue;
                }

                if ($this->checkTargetMatch($target, $spyItem)) {
                    $match[$idx] = $spyItem;
                }
            }

            echo "available supply nodes\n". json_encode($match). "\n";
            echo ("------------\n");

            // match nodes
            if ($match) {
                $sum = array_sum(array_column($match, 'remain'));
                foreach ($match as $mk => $mv) {
                    $supply[$mk]['remain'] = floor($mv['remain'] * (1 - $rate));
                }
                $dm['allocation'] = floor($sum * $rate);
            } else {
                $dm['allocation'] = 0;
            }

            $result[] = $dm;
        }
        return $result;
    }
}

$amount = new Amount();
$amount->setData($supply, $demand);
$ret = $amount->allocate();
echo ("\nallocation plan\n ");
var_dump(json_encode($ret));
echo "\n";

