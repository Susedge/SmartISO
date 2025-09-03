<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<style>
    .docx-guide-wrapper { max-width: 980px; }
    .prefix-legend { background:#fff; border:1px solid #e5e7eb; border-left:4px solid #6f42c1; border-radius:.5rem; padding:1rem 1.25rem; margin-bottom:1.5rem; }
    .prefix-legend code { font-weight:600; }
    .tag-badge { font-size:.65rem; letter-spacing:.5px; text-transform:uppercase; background:#343a40; }
    .tag-badge.badge-f { background:#6f42c1; }
    .tag-badge.badge-c { background:#0d6efd; }
    .docx-example-block { background:#fafafa; border:1px dashed #d0d7de; padding:1rem 1.25rem; border-radius:.5rem; }
    .docx-example-block code { font-weight:500; }
    .anchor-link { opacity:0; margin-left:.25rem; font-size:.8rem; text-decoration:none; }
    h3:hover .anchor-link, h2:hover .anchor-link, h1:hover .anchor-link { opacity:1; }
</style>
<div class="docx-guide-wrapper">
  <div class="d-flex align-items-center gap-2 mb-3">
      <h1 class="h3 mb-0">DOCX Variables Guide</h1>
      <span class="badge bg-secondary">Updated</span>
  </div>
  <div class="prefix-legend small">
      <p class="mb-2 fw-semibold text-muted">Tag Conventions Overview</p>
      <div class="row g-3 small">
        <div class="col-md-6">
            <div><span class="badge tag-badge badge-f">F_ (optional)</span> <strong>Single-value field</strong> – <code>F_REQUESTOR_NAME</code> or just <code>REQUESTOR_NAME</code>.</div>
            <div class="mt-1 text-muted">If both forms appear, the plain version is used – duplicates ignored.</div>
        </div>
        <div class="col-md-6">
            <div><span class="badge tag-badge badge-c">C_ (required)</span> <strong>Checkbox option</strong> – each option is its own tag: <code>C_SERVICES_LIGHTING</code>.</div>
            <div class="mt-1 text-muted">All tags sharing the same base (<code>C_SERVICES_*</code>) become one checkbox group.</div>
        </div>
      </div>
  </div>
  <p class="mb-4">Use Word Content Controls (Developer → Controls). Assign the <em>Tag</em> (or Alias) value to match these naming rules; the importer parses them automatically.</p>
  <h3 id="field-values" class="mt-4">1. Field Values (Single-Value)<a href="#field-values" class="anchor-link">#</a></h3>
  <p>Use uppercase plain or optional <code>F_</code> prefix:</p>
  <ul>
      <li>Field: priority_level → Tag: <code>PRIORITY_LEVEL</code> <em>or</em> <code>F_PRIORITY_LEVEL</code></li>
      <li>Field: justification → Tag: <code>JUSTIFICATION</code> or <code>F_JUSTIFICATION</code></li>
      <li>Field: needed_by → Tag: <code>NEEDED_BY</code> or <code>F_NEEDED_BY</code></li>
  </ul>
  <div class="docx-example-block mb-4">
      <div class="fw-semibold mb-1">Importer Normalization</div>
      <ol class="mb-0 small">
          <li>Strip optional leading <code>F_</code>.</li>
          <li>Lowercase final field name; keep underscores (e.g. <code>PRIORITY_LEVEL</code> → <code>priority_level</code>).</li>
          <li>Ignore duplicates (if both forms provided).</li>
      </ol>
  </div>
  <h3 id="checkbox-tags" class="mt-4">2. Checkbox / Multi-Select Option Tags (Grouped)<a href="#checkbox-tags" class="anchor-link">#</a></h3>
  <p>Define one tag per option using <code>C_</code> + <strong>BASE</strong> + <code>_</code> + <strong>OPTION</strong>. Group = all but last segment.</p>
  <ul>
      <li>Option: Lighting → <code>C_SERVICES_LIGHTING</code></li>
      <li>Option: Air Con → <code>C_SERVICES_AIR_CON</code></li>
      <li>Option: CCTV → <code>C_SERVICES_CCTV</code></li>
  </ul>
  <div class="docx-example-block mb-4">
      <div class="fw-semibold mb-1">Example: SERVICES Group</div>
      <pre class="mb-2 bg-light p-2 small border">C_SERVICES_LIGHTING
C_SERVICES_AIR_CON
C_SERVICES_CCTV</pre>
      <div class="small text-muted">Importer creates one checkbox field labeled “Services” with 3 options.</div>
  </div>
  <h3 id="notes" class="mt-4">Notes<a href="#notes" class="anchor-link">#</a></h3>
  <ul class="small">
      <li>Normal fields: plain or F_ accepted. Plain form wins if both present.</li>
      <li>Checkbox options: must start with <code>C_</code>. Group name = all but last segment; option label = last segment (underscores → spaces).</li>
      <li>Case-insensitive parsing, but UPPERCASE recommended for clarity.</li>
      <li>Non-alphanumeric characters are normalized to underscores.</li>
      <li>Date-like tags (e.g. <code>NEEDED_DATE</code>) auto-detected as date fields.</li>
  </ul>
</div>

<?= $this->endSection() ?>
