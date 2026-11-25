/**
 * ScholarLocal Frontend Interactions
 * Handles PDF viewer zoom/pagination, filter auto-submit, and anti-scraping safeguards.
 */
document.addEventListener('DOMContentLoaded', () => {
  // 1. Auto-submit filter checkboxes and sort dropdown on browse.php
  const filterForm = document.getElementById('browseFilterForm');
  if (filterForm) {
    filterForm.querySelectorAll('input[type="checkbox"], select').forEach((el) => {
      el.addEventListener('change', () => filterForm.submit());
    });
  }

  // 2. Protected PDF Viewer Controls (Zoom & Page navigation)
  const pdfPage = document.getElementById('pdfPagePreview');
  const zoomLabel = document.getElementById('pdfZoomLabel');
  const pageLabel = document.getElementById('pdfPageLabel');
  const pageNumFooter = document.getElementById('pdfPageFooter');
  let zoom = 100;
  let currentPage = 1;
  const totalPages = 86;

  const btnZoomIn = document.getElementById('btnZoomIn');
  const btnZoomOut = document.getElementById('btnZoomOut');
  const btnPrevPage = document.getElementById('btnPrevPage');
  const btnNextPage = document.getElementById('btnNextPage');

  if (pdfPage && zoomLabel) {
    const applyZoom = () => {
      pdfPage.style.transform = `scale(${zoom / 100})`;
      zoomLabel.textContent = `${zoom}%`;
    };
    btnZoomIn?.addEventListener('click', () => {
      zoom = Math.min(150, zoom + 25);
      applyZoom();
    });
    btnZoomOut?.addEventListener('click', () => {
      zoom = Math.max(75, zoom - 25);
      applyZoom();
    });
  }

  if (pageLabel) {
    const applyPage = () => {
      pageLabel.textContent = `${currentPage} / ${totalPages}`;
      if (pageNumFooter) pageNumFooter.textContent = String(currentPage);
    };
    btnPrevPage?.addEventListener('click', () => {
      currentPage = Math.max(1, currentPage - 1);
      applyPage();
    });
    btnNextPage?.addEventListener('click', () => {
      currentPage = Math.min(totalPages, currentPage + 1);
      applyPage();
    });
  }

  // 3. Anti-scraping / right-click deterrent inside protected PDF viewer
  const pdfViewer = document.getElementById('protectedPdfViewer');
  if (pdfViewer) {
    pdfViewer.addEventListener('contextmenu', (e) => e.preventDefault());
  }
});
