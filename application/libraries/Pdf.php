<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pdf - wrapper mPDF untuk CodeIgniter 3.
 * Menggunakan autoloader Composer (lihat $config['composer_autoload']).
 */
class Pdf
{
    /** @var \Mpdf\Mpdf */
    protected $mpdf;

    public function __construct($config = array())
    {
        $defaults = array(
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'orientation'      => 'P',
            'default_font_size' => 11,
            'tempDir'          => FCPATH . 'tmp',
        );

        if (! is_dir($defaults['tempDir'])) {
            @mkdir($defaults['tempDir'], 0777, TRUE);
        }

        $this->mpdf = new \Mpdf\Mpdf(array_merge($defaults, $config));
    }

    /**
     * Tulis HTML ke dokumen.
     */
    public function write_html($html)
    {
        $this->mpdf->WriteHTML($html);
        return $this;
    }

    /**
     * Output inline ke browser.
     */
    public function output($filename = 'dokumen.pdf', $dest = 'I')
    {
        return $this->mpdf->Output($filename, $dest);
    }

    public function get_mpdf()
    {
        return $this->mpdf;
    }
}
