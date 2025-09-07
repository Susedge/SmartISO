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
            <span class="badge bg-secondary">Updated Sep 6 2025</span>
    </div>
  <div class="prefix-legend small">
      <p class="mb-2 fw-semibold text-muted">Tag / Placeholder Prefix Quick Reference</p>
      <div class="row g-3 small">
        <div class="col-md-4">
            <div><span class="badge tag-badge badge-f">(plain)</span> <strong>Field value</strong> – <code>REQUESTOR_NAME</code></div>
            <div><span class="badge tag-badge badge-f">F_</span> <strong>Field value (alias)</strong> – <code>F_REQUESTOR_NAME</code></div>
            <div class="mt-1 text-muted">Plain and F_ resolve to the same value (duplicates ignored).</div>
        </div>
        <div class="col-md-4">
            <div><span class="badge tag-badge badge-c">B_</span> <strong>Block list</strong> – vertical list of options with the selected one prefixed by <code>◉</code>: <code>B_PRIORITY_LEVEL</code></div>
            <div class="mt-1 text-muted">Good for multi‑select / radio style tables.</div>
        </div>
        <div class="col-md-4">
            <div><span class="badge tag-badge badge-c">C_</span> <strong>Checkbox (REQUIRED PREFIX)</strong> – every checkbox option Tag MUST start with <code>C_</code>, e.g. <code>C_UNDER_WARRANTY_YES</code>, <code>C_UNDER_WARRANTY_NO</code>.</div>
            <div class="mt-1 text-muted">Without the <code>C_</code> prefix it is NOT treated as a checkbox symbol (it becomes a plain conditional label).</div>
        </div>
      </div>
  </div>
    <p class="mb-4"><strong>We now rely exclusively on Word Content Controls</strong> (Developer → Controls). Do <em>not</em> type <code>{{CURLY}}</code> placeholders; instead set the control's <strong>Tag</strong> (or Alias) to the token (e.g. <code>REQUESTOR_NAME</code>, <code>C_UNDER_WARRANTY_YES</code>). The exporter replaces the control contents with the resolved value or symbols. (Curly placeholders are ignored if present.)</p>
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
  <h3 id="checkbox-tags" class="mt-4">2. Checkbox & Multi‑Select Representations<a href="#checkbox-tags" class="anchor-link">#</a></h3>
  <p>You have several formatting choices depending on how you want the output to look in the generated DOCX/PDF.</p>
    <ol class="small mb-4">
    <li><strong>Checkboxes (only recognized symbol form):</strong> Tag = <code>C_FIELDNAME_OPTION</code>. Example: <code>C_UNDER_WARRANTY_YES</code>, <code>C_UNDER_WARRANTY_NO</code>. Each renders ☑ if selected else ☐. (Group tags like <code>C_FIELDNAME</code> are deprecated; use per‑option only.)</li>
    <li><strong>Plain field value:</strong> Tag = <code>FIELDNAME</code> (or <code>F_FIELDNAME</code>) outputs selected labels joined by commas (no symbols).</li>
    <li><strong>Conditional label (NOT a checkbox):</strong> Tag = <code>FIELDNAME_OPTION</code> (no C_) returns the option text only if selected (used for dynamic sentences).</li>
        <li><strong>Legacy markers (optional/backward compatibility):</strong> <code>A_1</code>, <code>A_2</code> etc. still resolve but are not required for new templates.</li>
    </ol>
  <div class="docx-example-block mb-4">
      <div class="fw-semibold mb-1">Boolean Field Example: UNDER_WARRANTY</div>
    <pre class="mb-2 bg-light p-2 small border">Tag: C_UNDER_WARRANTY_YES  => ☑ or ☐
Tag: C_UNDER_WARRANTY_NO   => ☑ or ☐
Tag: UNDER_WARRANTY_YES    => Yes (conditional label, not a checkbox)
Tag: UNDER_WARRANTY_NO     => No  (conditional label, not a checkbox)
Tag: UNDER_WARRANTY        => Yes (plain joined value)
</pre>
      <div class="small text-muted">Use whichever best matches your template layout. Inline (<code>C_</code>) is most similar to manual forms with square boxes.</div>
  </div>
  <h3 id="notes" class="mt-4">Notes<a href="#notes" class="anchor-link">#</a></h3>
  <ul class="small">
      <li>Normal fields: plain or F_ accepted. Plain form wins if both present.</li>
    <li>Content control priority: We evaluate the Tag (or Alias). Curly braces are ignored; only control metadata matters.</li>
    <li><strong>All checkbox symbols require the <code>C_</code> prefix.</strong> Tags without <code>C_</code> are treated as plain text labels.</li>
    <li><code>C_FIELDNAME_OPTION</code> → single checkbox symbol per option (only supported checkbox Tag format).</li>
    <li><code>FIELDNAME_OPTION</code> (no C_) returns the label if selected; blank if not (useful for inline sentences).</li>
    <li><code>B_FIELDNAME</code> produces one paragraph per option with ◉ for selected ones.</li>
      <li>Case-insensitive parsing, but UPPERCASE recommended for clarity.</li>
      <li>Non-alphanumeric characters are normalized to underscores.</li>
      <li>Date-like tags (e.g. <code>NEEDED_DATE</code>) auto-detected as date fields.</li>
      <li>Signature image placeholders use the <code>P_</code> prefix (see Signatures section below).</li>
  </ul>

    <h3 id="signatures" class="mt-4">3. Signature Placeholders<a href="#signatures" class="anchor-link">#</a></h3>
    <p>Insert <strong>Picture Content Controls</strong> (preferred) whose <em>Tag</em> matches one of the following to have the system inject the captured signature images for completed forms. Variable-style image placeholders using the same names still work but content controls are future-proof.</p>
  <ul>
      <li><code>P_APPROVER_SIGNATURE</code> – Approver's signature image</li>
      <li><code>P_SERVICE_STAFF_SIGNATURE</code> – Service staff signature image</li>
      <li><code>P_REQUESTOR_SIGNATURE</code> – Requestor's confirmation signature image</li>
  </ul>
  <div class="docx-example-block mb-4">
      <div class="fw-semibold mb-1">Example (Word)</div>
      <ol class="mb-0 small">
          <li>Enable the Developer tab.</li>
          <li>Insert a <em>Picture Content Control</em>.</li>
          <li>Set its Tag (and optionally Title) to <code>P_REQUESTOR_SIGNATURE</code>.</li>
          <li>Leave it blank – the exporter replaces it with the actual image.</li>
      </ol>
  </div>
  <ul class="small mb-4">
      <li>Signatures are injected only after the corresponding workflow step: approver signs (approval date), service staff signs (service completion), requestor final confirmation.</li>
      <li>If a user has not uploaded a signature image, the control remains blank.</li>
      <li>Supported formats: PNG or JPEG; images are stored and injected at native size (scaled by Word layout).</li>
  </ul>

  <h3 id="troubleshooting" class="mt-4">4. Troubleshooting<a href="#troubleshooting" class="anchor-link">#</a></h3>
  <div class="docx-example-block mb-4 small">
      <ul class="mb-0">
          <li><strong>Blank checkbox:</strong> Ensure Tag exactly matches <code>C_FIELDNAME_OPTION</code> (no spaces, punctuation normalized to underscores).</li>
          <li><strong>Wrong value shown:</strong> Verify the field name in the form builder matches the Tag (case-insensitive but spelling must align).</li>
          <li><strong>Signature missing:</strong> Confirm the user uploaded their signature and the submission has reached the required status (e.g. completed).</li>
          <li><strong>Control not replaced:</strong> Check it is a Content Control (not plain text with braces). Set its Tag in Properties, not just visible text.</li>
          <li><strong>Need literal braces:</strong> Type them normally; the exporter ignores plain <code>{{TEXT}}</code> not wrapped in a content control.</li>
      </ul>
  </div>
</div>

<?= $this->endSection() ?>
