#!/usr/bin/env bash
# Downloads all third-party libraries into ./vendor so the PDF tool runs FULLY OFFLINE.
# Run:  bash vendor/download_libs.sh
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p tesseract
declare -A files=(
  [pdf-lib.min.js]="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"
  [pdf.min.js]="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"
  [pdf.worker.min.js]="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js"
  [sweetalert2.all.min.js]="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"
  [tesseract/tesseract.min.js]="https://unpkg.com/tesseract.js@v5.0.3/dist/tesseract.min.js"
  [tesseract/worker.min.js]="https://unpkg.com/tesseract.js@v5.0.3/dist/worker.min.js"
  [tesseract/tesseract-core.wasm.js]="https://unpkg.com/tesseract.js@v5.0.3/dist/tesseract-core.wasm.js"
  [tesseract/tesseract-core.wasm]="https://unpkg.com/tesseract.js@v5.0.3/dist/tesseract-core.wasm"
  [tesseract/eng.traineddata.gz]="https://raw.githubusercontent.com/naptha/tessdata/master/eng.traineddata.gz"
)
for out in "${!files[@]}"; do
  echo "Downloading ${files[$out]}"
  curl -fsSL "${files[$out]}" -o "$out"
done
echo "Done. Libraries are in ./vendor. The tool now uses them automatically (offline)."
