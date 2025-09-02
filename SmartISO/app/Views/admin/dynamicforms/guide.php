<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<h1><?= esc($title) ?></h1>

<p>For DOCX templates, use Word Content Controls (Developer → Controls) with Tag names following these patterns:</p>

<h3>1. Field Values</h3>
<p>Use the field name in uppercase:</p>
<ul>
    <li>Field: priority_level → Tag: PRIORITY_LEVEL</li>
    <li>Field: justification → Tag: JUSTIFICATION</li>
    <li>Field: needed_by → Tag: NEEDED_BY</li>
</ul>

<h3>2. Checkbox Options</h3>
<p>For checkbox/multi-select fields, each option gets its own tag:</p>
<ul>
    <li>Field: services, Option: Lighting → Tag: SERVICES_LIGHTING</li>
    <li>Field: services, Option: Air Con → Tag: SERVICES_AIR_CON</li>
    <li>Field: services, Option: CCTV → Tag: SERVICES_CCTV</li>
</ul>

<h4>Example</h4>
<p>If a user selects "Lighting" and "CCTV" for the services field:</p>
<ul>
    <li>SERVICES = "Lighting, CCTV"</li>
    <li>SERVICES_LIGHTING = "Lighting"</li>
    <li>SERVICES_CCTV = "CCTV"</li>
    <li>SERVICES_AIR_CON = (blank)</li>
</ul>

<h4>Notes</h4>
<ul>
    <li>Tags are case-insensitive but use UPPERCASE for consistency</li>
    <li>Option tags only contain text when that option is selected</li>
    <li>Non-alphanumeric characters in option names become underscores</li>
</ul>

<?= $this->endSection() ?>
