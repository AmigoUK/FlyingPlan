<?php

namespace App\Services;

/**
 * PDF report generator using mPDF.
 * Replaces Python's WeasyPrint with mPDF for HTML-to-PDF conversion.
 *
 * Ported from services/pdf_report.py.
 */
class PdfReport
{
    /**
     * Generate a PDF flight report for an order.
     *
     * @param int  $orderId          Order ID
     * @param bool $includeAdminNotes Include admin notes in report
     * @return string PDF binary content
     */
    public static function generateReportPdf(int $orderId, bool $includeAdminNotes = false): string
    {
        $db = \Config\Database::connect();

        $order = $db->table('orders')->where('id', $orderId)->get()->getRow();
        $flightPlan = $db->table('flight_plans')->where('id', $order->flight_plan_id)->get()->getRow();
        $waypoints = $db->table('waypoints')
            ->where('flight_plan_id', $flightPlan->id)
            ->orderBy('`index`')
            ->get()->getResult();
        $riskAssessment = $db->table('risk_assessments')->where('order_id', $orderId)->get()->getRow();
        $activities = $db->table('order_activities oa')
            ->select('oa.*, u.display_name as user_name')
            ->join('users u', 'u.id = oa.user_id', 'left')
            ->where('oa.order_id', $orderId)
            ->orderBy('oa.created_at', 'ASC')
            ->get()->getResult();

        $pilot = null;
        if ($order->pilot_id) {
            $pilot = $db->table('users')->where('id', $order->pilot_id)->get()->getRow();
        }

        $pois = $db->table('pois')
            ->where('flight_plan_id', $flightPlan->id)
            ->orderBy('sort_order')
            ->get()->getResult();

        $settings = (new \App\Models\AppSettingsModel())->getSettings();

        // Generate static map
        $mapDataUri = StaticMap::generateStaticMapDataUri($flightPlan, $waypoints, $pois);

        // Render HTML from view
        $html = view('reports/flight_report', [
            'order'              => $order,
            'flight_plan'        => $flightPlan,
            'waypoints'          => $waypoints,
            'risk_assessment'    => $riskAssessment,
            'activities'         => $activities,
            'pilot'              => $pilot,
            'settings'           => $settings,
            'map_data_uri'       => $mapDataUri,
            'include_admin_notes' => $includeAdminNotes,
        ]);

        // Generate PDF with mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'tempDir'       => WRITEPATH . 'mpdf',
        ]);

        $mpdf->SetTitle('Flight Report - ' . ($flightPlan->reference ?? ''));
        $mpdf->SetAuthor($settings->business_name ?? 'FlyingPlan');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
