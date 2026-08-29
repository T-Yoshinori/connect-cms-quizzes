#!/usr/bin/env bash
set -euo pipefail

version="${1:-0.9.0-beta.3}"
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
build_root="${repo_root}/build"
standard_name="connect-cms-quizzes-${version}"
option_name="connect-cms-quizzes-math-editor-tools-option-${version}"

rm -rf "${build_root}"
mkdir -p "${build_root}/${standard_name}" "${build_root}/${option_name}"

for source_dir in app database resources; do
    cp -a "${repo_root}/${source_dir}" "${build_root}/${standard_name}/"
done
cp -a "${repo_root}/options/math-editor-tools/overlay/." "${build_root}/${option_name}/"

cp "${repo_root}/README.md" "${repo_root}/LICENSE" "${repo_root}/CHANGELOG.md" \
   "${repo_root}/SECURITY.md" "${build_root}/${standard_name}/"
cp "${repo_root}/options/math-editor-tools/README.md" \
   "${build_root}/${option_name}/README.md"

(
    cd "${build_root}/${standard_name}"
    find . -type f -print | LC_ALL=C sort > MANIFEST.txt
)
(
    cd "${build_root}/${option_name}"
    find . -type f -print | LC_ALL=C sort > MANIFEST.txt
)

if find "${build_root}" -type f \( -name '.env' -o -name '.env.*' -o \
    -name '*.log' -o -name '*.sql' -o -name '*.sqlite' -o \
    -name '*.sqlite3' -o -name '*.bak' \) -print | grep -q .; then
    echo "ERROR: prohibited file found" >&2
    exit 1
fi

(
    cd "${build_root}"
    zip -qr "${standard_name}.zip" "${standard_name}"
    zip -qr "${option_name}.zip" "${option_name}"
)

echo "${build_root}/${standard_name}.zip"
echo "${build_root}/${option_name}.zip"
