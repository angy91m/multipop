<?php
defined('ABSPATH') || exit;

require_once(MULTIPOP_PLUGIN_PATH . '/includes/fpdi/src/autoload.php');
require_once(MULTIPOP_PLUGIN_PATH . '/includes/tcpdf/tcpdf.php');
$mpop_cf_paths = array_filter(scandir(MULTIPOP_PLUGIN_PATH . '/includes/tcpdf/fonts'), function($s) {return preg_match('/\.ttf$/i', $s);} );
foreach($mpop_cf_paths as $p) {
    TCPDF_FONTS::addTTFfont(MULTIPOP_PLUGIN_PATH . '/includes/tcpdf/fonts/' . $p);
}
class MultipoPDF extends \setasign\Fpdi\Tcpdf\Fpdi
{
    private $total_count;
    private static $default_config = [
        'orientation' => 'P',
        'unit' => 'mm',
        'mode' => 'UTF-8',
        'format' => 'A4',
        'font_size' => 8,
        'font' => 'helvetica',
        'margin_top' => 15,
        'margin_header' => 10,
        'margin_bottom' => 15,
        'margin_footer' => 7,
        'margin_left' => 11,
        'margin_right' => 11,
        'mpop_import' => false
    ];
    public array $config = [];
    private static array $tagvs = [
        'div' => [
            0 => ['h' => 0, 'n' => 0],
            1 => ['h' => 0, 'n' => 0]
        ],
        // 'p' => [
        //     0 => ['h' => 0, 'n' => 0],
        //     1 => ['h' => 0, 'n' => 0]
        // ],
        'span' => [
            0 => ['h' => 0, 'n' => 0],
            1 => ['h' => 0, 'n' => 0]
        ],
        'h2' => [
            0 => ['h' => 0, 'n' => 0],
            1 => ['h' => 0, 'n' => 0]
        ]
    ];

    private array $imported_fd = [];

    public function __construct(array $config = [])
    {
        $config += self::$default_config;
        $this->config = $config;
        parent::__construct(
            $config['orientation'],
            $config['unit'],
            $config['format'],
            true,
            $config['mode']
        );

        $this->setHtmlVSpace(self::$tagvs);

        if (isset($this->config['title']) && $this->config['title'] !== null) {
            $this->setTitle($this->config['title']);
            $this->setHeaderData('', '', $this->config['title'], '');
        }

        $this->setCreator('Multipopolare');
        $this->setAuthor('Multipopolare');

        $this->setFont($config['font'], '', $config['font_size']);
        $this->setHeaderFont([$config['font'], 'B', $config['font_size']]);
        $this->setFooterFont([$config['font'], 'B', $config['font_size']]);

        //set margins
        $this->setMargins($config['margin_left'], $config['margin_top'], $config['margin_right'], true);
        $this->setHeaderMargin($config['margin_header']);
        $this->setFooterMargin($config['margin_footer']);

        //set auto page breaks
        $this->setAutoPageBreak(true, $config['margin_bottom']);
        if (!$config['mpop_import']) {
            $this->AddPage();
        }
    }
    /**
     * Page header
     *
     * @see TCPDF::Header()
    **/
    public function Header() // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if (!$this->config['mpop_import']) {
            $this->SetLineStyle( [ 'width' => 0.5, 'color' => [89,139,133]]);

            $this->Line(0+7,13,$this->getPageWidth()-7,13); 
            $this->Line($this->getPageWidth()-7,0+13,$this->getPageWidth()-7,$this->getPageHeight()-15);
            $this->Line(0+7,$this->getPageHeight()-15,$this->getPageWidth()-7,$this->getPageHeight()-15);
            $this->Line(0+7,0+13,0+7,$this->getPageHeight()-15);
            // Title
            if ($this->title) {
                $this->SetFont('helveticatitle', '', 12);
                $this->setY($this->config['margin_header']+9.1);
                $this->Cell(0, 0, $this->title, 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetFont($this->config['font'], '', $this->config['font_size']);
            }
            // Logo
            if (isset($this->config['logo']) && $this->config['logo']) {
                $this->Image($this->config['logo'], $this->getPageWidth()-$this->config['margin_right']-30.7, $this->config['margin_top']-12.3, 40);
            }
        }
    }


    /**
     * Page footer
     *
     * @see TCPDF::Footer()
    **/
    public function Footer() // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if (!$this->config['mpop_import']) {
            $text = '';
            if (isset($this->config['export_ref'])) {
                $text = (isset($this->config['export_ref_label']) ? $this->config['export_ref_label'] : 'Export ref: ') . $this->config['export_ref'] . ' - ';
            }
            $text .= sprintf("Pag.: %s/%s", $this->getAliasNumPage(), $this->getAliasNbPages());
            $this->SetY(-$this->config['margin_bottom']);
            // Page number
            $this->Cell(0, $this->config['margin_footer'], $text, 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    /**
     * Get the list of available fonts.
     *
     * @return array Array of "filename" => "font name"
     **/
    public static function getFontList()
    {

        $list = [];

        $path = TCPDF_FONTS::_getfontpath();

        // Includes will be made inside a function to ensure that declared variables are
        // only available inside the function scope, and will so not affect other elements from loop.
        // Also, varibales declared in font file will be automatically garbage collected (some are huge).
        $include_fct = function ($font_path) use (&$list) {
            $name = null;
            $type = null;

            include $font_path;

            if ($name === null) {
                return; // Not a font file
            }

            $font = basename($font_path, '.php');

            // skip subfonts
            if (
                ((substr($font, -1) == 'b') || (substr($font, -1) == 'i'))
                && isset($list[substr($font, 0, -1)])
            ) {
                return;
            }
            if (
                ((substr($font, -2) == 'bi'))
                && isset($list[substr($font, 0, -2)])
            ) {
                return;
            }

            if ($type == 'cidfont0') {
                // cidfont often have the same name (ArialUnicodeMS)
                $list[$font] = sprintf(__('%1$s (%2$s)'), $name, $font);
            } else {
                $list[$font] = $name;
            }
        };

        foreach (glob($path . '/*.php') as $font_path) {
            $include_fct($font_path);
        }
        return $list;
    }

    public function export_file() {
        return $this->Output('', 'S');
    }

    public function setSourceFile($fd) {
        if (is_resource($fd)) {
            $this->imported_fd[] = $fd;
        }
        return parent::setSourceFile($fd);
    }

    public function AddSingleImage(string $img = '', $path = false) {
        $image = @imagecreatefromstring($path ? file_get_contents($img) : $img);
        if (!$image) {
            throw new Exception("Unable to load image");
        }
        $imgWidthPx = imagesx($image);
        $imgHeightPx = imagesy($image);
        $dpi = 72;
        $imgWidthMm = $imgWidthPx / $dpi * 25.4;
        $imgHeightMm = $imgHeightPx / $dpi * 25.4;
        $orientation = $imgHeightPx > $imgWidthPx ? 'P' : 'L';
        $pageWidth = $orientation === 'L' ? 297 : 210;
        $pageHeight = $orientation === 'L' ? 210 : 297;
        $margin = 10;
        $usableWidth = $pageWidth - (2 * $margin);
        $usableHeight = $pageHeight - (2 * $margin);
        if ($imgWidthMm > $usableWidth || $imgHeightMm > $usableHeight) {
            $scale = min($usableWidth / $imgWidthMm, $usableHeight / $imgHeightMm);
            $newWidthMm = $imgWidthMm * $scale;
            $newHeightMm = $imgHeightMm * $scale;
        } else {
            $newWidthMm = $imgWidthMm;
            $newHeightMm = $imgHeightMm;
        }
        $x = ($pageWidth - $newWidthMm) / 2;
        $y = ($pageHeight - $newHeightMm) / 2;
        $this->AddPage($orientation, [$pageWidth, $pageHeight]);
        imagesavealpha($image, true);
        $transparentBg = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparentBg);
        ob_start();
        imagepng($image, null, 0);
        $image_content = ob_get_clean();
        $this->Image("@$image_content", $x, $y, $newWidthMm, $newHeightMm, '', '', '', true, 300);
    }

    public function __destruct() {
        if (!empty($this->imported_fd)) {
            foreach($this->imported_fd as $fd) {
                fclose($fd);
            }
        }
        @$this->cleanUp();
        parent::__destruct();
    }

    // public function setTotalCount($count)
    // {
    //     $this->total_count = $count;
    //     return $this;
    // }
}