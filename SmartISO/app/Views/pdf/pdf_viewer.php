<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Form Submission #<?= $submission['id'] ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            font-size: 12px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .logo {
            max-width: 200px;
            margin-bottom: 15px;
        }
        .form-title { 
            font-size: 24px; 
            font-weight: bold; 
            margin-bottom: 10px; 
        }
        .section { 
            margin-bottom: 20px; 
        }
        .section-title { 
            font-size: 18px; 
            font-weight: bold; 
            margin-bottom: 10px; 
            border-bottom: 1px solid #ccc; 
            padding-bottom: 5px; 
        }
        .field { 
            margin-bottom: 10px; 
        }
        .field-label { 
            font-weight: bold; 
        }
        .signatures { 
            margin-top: 50px; 
        }
        .signature { 
            margin-bottom: 30px; 
        }
        .signature-image {
            max-height: 50px;
            margin-top: 10px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        table, th, td { 
            border: 1px solid #ddd; 
        }
        th, td { 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #f2f2f2; 
        }
        .page-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Header with company logo and form information -->
    <div class="header">
        <!-- You could add your company logo here -->
        <!-- <img src="<?= base_url('assets/images/logo.png') ?>" class="logo"> -->
        <div class="form-title"><?= esc($form['description']) ?> (Form #<?= esc($form['code']) ?>)</div>
        <div>Submission ID: <?= $submission['id'] ?></div>
        <div>Status: <?= ucfirst($submission['status']) ?></div>
        <div>Submitted on: <?= date('M d, Y', strtotime($submission['created_at'])) ?></div>
    </div>
    
    <!-- Form data -->
    <div class="section">
        <div class="section-title">Form Details</div>
        <table>
            <tr>
                <th>Field</th>
                <th>Value</th>
            </tr>
            <?php foreach ($panel_fields as $field): ?>
                <?php 
                    $fieldName = $field['field_name'];
                    $fieldLabel = $field['field_label'];
                    $fieldValue = isset($submission_data[$fieldName]) ? $submission_data[$fieldName] : '';
                ?>
                <tr>
                    <td class="field-label"><?= esc($fieldLabel) ?></td>
                    <td><?= esc($fieldValue) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <!-- Signatures section -->
    <div class="section signatures">
        <div class="section-title">Signatures</div>
        
        <!-- Requestor signature -->
        <div class="signature">
            <div class="field-label">Requested by:</div>
            <div><?= esc($requestor['full_name']) ?></div>
            <?php if (!empty($submission['requestor_signature_date'])): ?>
                <div>Signed on: <?= date('M d, Y', strtotime($submission['requestor_signature_date'])) ?></div>
                <?php if (!empty($requestor['signature'])): ?>
                    <!-- If you store signature images, you could display them here -->
                    <img src="<?= base_url('uploads/signatures/' . $requestor['signature']) ?>" class="signature-image">
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Approver signature -->
        <?php if ($approver): ?>
            <div class="signature">
                <div class="field-label">Approved by:</div>
                <div><?= esc($approver['full_name']) ?></div>
                <?php if (!empty($submission['approval_comments'])): ?>
                    <div>Comments: <?= esc($submission['approval_comments']) ?></div>
                <?php endif; ?>
                <?php if (!empty($approver['signature'])): ?>
                    <img src="<?= base_url('uploads/signatures/' . $approver['signature']) ?>" class="signature-image">
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Service staff signature -->
        <?php if ($service_staff): ?>
            <div class="signature">
                <div class="field-label">Serviced by:</div>
                <div><?= esc($service_staff['full_name']) ?></div>
                <?php if (!empty($submission['service_notes'])): ?>
                    <div>Service Notes: <?= esc($submission['service_notes']) ?></div>
                <?php endif; ?>
                <?php if (!empty($submission['service_staff_signature_date'])): ?>
                    <div>Signed on: <?= date('M d, Y', strtotime($submission['service_staff_signature_date'])) ?></div>
                    <?php if (!empty($service_staff['signature'])): ?>
                        <img src="<?= base_url('uploads/signatures/' . $service_staff['signature']) ?>" class="signature-image">
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="page-footer">
        Generated on <?= date('M d, Y H:i:s') ?> | Form ID: <?= $form['id'] ?> | Submission ID: <?= $submission['id'] ?>
    </div>
</body>
</html>
