<?php

/**
 * Professional Quiz Result Email Template
 * Variables expected: $student_name, $quiz_title, $course_title, $score, $points, $total_points, $status
 */
?>
<div style="text-align: center; margin-bottom: 30px;">
    <img src="cid:edulux_logo_v1" alt="EduLux Logo" style="max-height: 70px; width: auto; margin-bottom: 20px;">
    
    <p style="text-transform: uppercase; letter-spacing: 2px; font-size: 12px; font-weight: 900; color: #6366f1; margin: 0;">Official Grade Report</p>
    <h2 style="font-style: italic; font-size: 24px; margin: 10px 0; color: #0f172a;">Assessment Accomplished</h2>
</div>

<div style="background-color: #f8fafc; border-radius: 24px; padding: 40px; text-align: center; border: 1px solid #e2e8f0; margin-bottom: 40px;">
    <p style="font-size: 14px; font-weight: bold; color: #64748b; text-transform: uppercase; margin: 0;">Performance Score</p>
    <div style="font-size: 64px; font-weight: 900; color: #0f172a; margin: 10px 0; line-height: 1;">
        <?= $score ?>%
    </div>
    
    <?php $displayStatus = $status ?? $status_label ?? 'Completed'; ?>

    <div style="display: inline-block; padding: 6px 16px; border-radius: 100px; font-size: 12px; font-weight: 800; text-transform: uppercase; 
        background-color: <?= ($displayStatus === 'Passed') ? '#dcfce7' : '#f1f5f9' ?>; 
        color: <?= ($displayStatus === 'Passed') ? '#15803d' : '#475569' ?>;">
        <?= $displayStatus ?>
    </div>
</div>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px; font-size: 15px;">
    <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;">Course</td>
        <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #0f172a; text-align: right; font-weight: bold;"><?= $course_title ?></td>
    </tr>
    <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;">Assessment</td>
        <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #0f172a; text-align: right; font-weight: bold;"><?= $quiz_title ?></td>
    </tr>
    <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;">Points Earned</td>
        <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #0f172a; text-align: right; font-weight: bold;"><?= $points ?> / <?= $total_points ?></td>
    </tr>
    <tr>
        <td style="padding: 12px 0; color: #64748b;">Date Attempted</td>
        <td style="padding: 12px 0; color: #0f172a; text-align: right; font-weight: bold;"><?= date('M d, Y') ?></td>
    </tr>
</table>

<div style="color: #475569; font-size: 14px; text-align: center; font-style: italic;">
    "The beautiful thing about learning is that no one can take it away from you."
</div>