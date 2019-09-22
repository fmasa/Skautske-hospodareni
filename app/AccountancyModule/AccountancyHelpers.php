<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Cake\Chronos\Date;
use DateTimeInterface;
use InvalidArgumentException;
use Model\Payment\Payment\State;
use Money\Money;
use Nette\Utils\Html;
use RuntimeException;
use function array_reverse;
use function array_shift;
use function count;
use function explode;
use function func_get_args;
use function is_callable;
use function mb_strtoupper;
use function mb_substr;
use function number_format;
use function preg_replace;
use function sprintf;
use function str_split;
use function strlen;
use function strpos;
use function substr;

abstract class AccountancyHelpers
{
    private const DATE_FORMAT_FULL      = 'j. n. Y';
    private const DATE_FORMAT_DAY_MONTH = 'j. n.';
    private const DATE_FORMAT_DAY       = 'j.';

    /**
     * loader na všechny filtry
     *
     * @param int|float|string|DateTimeInterface|Money|State|null $value
     *
     * @return Html|string
     */
    public static function loader(string $filter, $value)
    {
        $method = [self::class, $filter];

        if (is_callable($method)) {
            $args = func_get_args();
            array_shift($args);

            return $method(...$args);
        }

        throw new RuntimeException('Filter not found');
    }

    /**
     * zobrazení stavu ve formě ikony
     */
    public static function eventStateLabel(string $s) : string
    {
        if ($s === 'draft') {
            return '<span class=\'badge badge-warning\'>Rozpracováno</span>';
        }

        if ($s === 'closed') {
            return '<span class=\'badge badge-success\'>Uzavřeno</span>';
        }

        return '<span class=\'badge badge-default\'>Zrušeno</span>';

        //draft, closed, cancelled
    }

    public static function eventStateLabelNew(string $state) : string
    {
        if ($state === 'draft') {
            return '<span class="badge badge-warning">Rozpracováno</span>';
        }

        if ($state === 'closed') {
            return '<span class="badge badge-success">Uzavřeno</span>';
        }

        return '<span class="badge badge-default">Zrušeno</span>';
    }

    /**
     * zobrazuje popisky stavů u táborů
     */
    public static function campStateLabel(string $s) : string
    {
        switch ($s) {
            case 'draft':
                return '<span class=\'badge badge-warning\'>Rozpracováno</span>';
            case 'approvedParent':
                return '<span class=\'badge badge-info\'>Schválený střediskem</span>';
            case 'approvedLeader':
                return '<span class=\'badge badge-info\'>Schválený vedoucím</span>';
            case 'real':
                return '<span class=\'badge badge-success\'>Skutečnost odevzdána</span>';
            default:
                return '<span class=\'badge badge-default\'>Zrušený</span>';
        }
    }

    public static function commandState(?DateTimeInterface $s) : string
    {
        if ($s === null) {
            return '<span class="hidden-xs hidden-sm badge badge-warning">Rozpracovaný</span>';
        }

        return '<span class="badge badge-success" title="Uzavřeno dne: ' .
            $s->format('j.n.Y H:i:s') . '">Uzavřený</span>';
    }

    public static function paymentState(string $state, bool $plural) : string
    {
        $labels = [
            State::PREPARING => ['Připravena', 'Připravené'],
            State::COMPLETED => ['Dokončena', 'Dokončené'],
            State::SENT => ['Odeslána', 'Odeslané'],
            State::CANCELED => ['Zrušena', 'Zrušené'],
        ];

        return $labels[$state][$plural ? 1 : 0] ?? $state;
    }

    public static function paymentStateLabel(State $s) : Html
    {
        $classes = [
            State::PREPARING => 'info',
            State::COMPLETED => 'success',
            State::SENT => 'primary',
            State::CANCELED => 'danger',
        ];

        return Html::el('span')
            ->setText(self::paymentState($s->toString(), false))
            ->setAttribute('class', 'badge badge-' . ($classes[$s->toString()] ?? 'secondary'));
    }

    /**
     * formátuje číslo na částku
     *
     * @param float|string|Money|null $price
     * http://prirucka.ujc.cas.cz/?id=786
     */
    public static function price($price, bool $full = true) : string
    {
        if ($price === null || $price === '') {
            return ' '; //je tam nedělitelná mezera
        }
        $decimals = $full ? 2 : 0;

        if ($price instanceof Money) {
            $price = (float) $price->getAmount() / 100;
        }

        return number_format((float) $price, $decimals, ',', ' '); //nedělitelná mezera
    }

    /**
     * formátuje číslo podle toho zda obsahuje desetinou část nebo ne
     *
     * @param int|float|string $num
     */
    public static function num($num) : string
    {
        return number_format((float) $num, strpos((string) $num, '.') ? 2 : 0, ',', ' ');
    }

    public static function postCode(string $oldPsc) : string
    {
        $psc = preg_replace('[^0-9]', '', $oldPsc);
        if (strlen($psc) === 5) {
            return substr($psc, 0, 3) . ' ' . substr($psc, -2);
        }

        return $oldPsc;
    }

    /**
     * převádí zadané číslo na slovní řetězec
     */
    public static function priceToString(float $price) : string
    {
        //@todo ošetření správného tvaru

        $_jednotky = [
            0 => '',
            1 => 'jedna',
            2 => 'dva',
            3 => 'tři',
            4 => 'čtyři',
            5 => 'pět',
            6 => 'šest',
            7 => 'sedm',
            8 => 'osm',
            9 => 'devět',
            10 => 'deset',
            11 => 'jedenáct',
            12 => 'dvanáct',
            13 => 'třináct',
            14 => 'čtrnáct',
            15 => 'patnáct',
            16 => 'šestnáct',
            17 => 'sedmnáct',
            18 => 'osmnáct',
            19 => 'devatenáct',
        ];

        $_desitky = [
            0 => '',
            1 => '',
            2 => 'dvacet',
            3 => 'třicet',
            4 => 'čtyřicet',
            5 => 'padesát',
            6 => 'šedesát',
            7 => 'sedmdesát',
            8 => 'osmdesát',
            9 => 'devadesát',
        ];
        $_sta     = [
            0 => '',
            1 => 'jednosto',
            2 => 'dvěstě',
            3 => 'třista',
            4 => 'čtyřista',
            5 => 'pětset',
            6 => 'šestset',
            7 => 'sedmset',
            8 => 'osmset',
            9 => 'devětset',
        ];
        $_tisice  = [
            0 => '',
            1 => 'jedentisíc',
            2 => 'dvatisíce',
            3 => 'třitisíce',
            4 => 'čtyřitisíce',
        ];

        $string  = '';
        $parts   = explode('.', (string) $price, 2); //0-pred 1-za desitou čárkou
        $numbers = array_reverse(str_split($parts[0]));

        if (count($numbers) > 6) {
            return 'PŘÍLIŠ VYSOKÉ ČÍSLO';
        }

        for ($i = count($numbers); $i < 6; ++$i) { //doplnění nezaplněných řádu
            $numbers[$i] = 0;
        }

        //tisice
        $nTisice = (int) ($numbers[5] . $numbers[4] . $numbers[3]);
        if ($nTisice <= 4) {
            $string .= $_tisice[$numbers[3]];
        } elseif ($nTisice < 20) {
            $string .= $_jednotky[(int) ($numbers[4] . $numbers[3])] . 'tisíc';
        } elseif ($nTisice < 100) {
            $string .= $_desitky[$numbers[4]] . $_jednotky[$numbers[3]] . 'tisíc';
        } else {
            $string .= $_sta[$numbers[5]] . $_desitky[$numbers[4]] . $_jednotky[$numbers[3]] . 'tisíc';
        }

        //sta
        $string .= $_sta[$numbers[2]];

        //desitky a jednotky
        $nDesitky = (int) ($numbers[1] . $numbers[0]);
        if ($nDesitky < 20) {
            $string .= $_jednotky[$nDesitky];
        } else {
            $string .= $_desitky[$numbers[1]] . $_jednotky[$numbers[0]];
        }

        return mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    public static function yesno(bool $b) : string
    {
        return $b ? 'Ano' : 'Ne';
    }

    public static function groupState(string $s) : string
    {
        switch ($s) {
            case 'open':
                return '<span class=\'badge badge-success\'>Otevřená</span>';
            case 'closed':
                return '<span class=\'badge badge-warning\'>Uzavřená</span>';
            case 'canceled':
                return '<span class=\'badge badge-default\'>Zrušená</span>';
            default:
                return '<span class=\'badge\'>Neznámý</span>';
        }
    }

    /**
     * @param Date[] $dates
     */
    public static function dateRange(array $dates) : string
    {
        if (count($dates) !== 2) {
            throw new InvalidArgumentException('Filter expect array of 2 items.');
        }
        [$start, $end] = $dates;
        if ($start->year !== $end->year) {
            return sprintf('%s - %s', $start->format(self::DATE_FORMAT_FULL), $end->format(self::DATE_FORMAT_FULL));
        }
        if ($start->month !== $end->month) {
            return sprintf('%s - %s', $start->format(self::DATE_FORMAT_DAY_MONTH), $end->format(self::DATE_FORMAT_FULL));
        }
        if ($start->day !== $end->day) {
            return sprintf('%s - %s', $start->format(self::DATE_FORMAT_DAY), $end->format(self::DATE_FORMAT_FULL));
        }

        return $end->format(self::DATE_FORMAT_FULL);
    }
}
