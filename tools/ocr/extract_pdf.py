import argparse
import json
import os
import sys


def emit(payload, enabled):
    if enabled:
        print(json.dumps(payload, ensure_ascii=False))


def parse_args():
    parser = argparse.ArgumentParser(description="Extract text from a scanned PDF with Tesseract.")
    parser.add_argument("input_pdf")
    parser.add_argument("output_text")
    parser.add_argument("--tesseract")
    parser.add_argument("--poppler")
    parser.add_argument("--language", default="eng")
    parser.add_argument("--dpi", type=int, default=300)
    parser.add_argument("--max-pages", type=int, default=500)
    parser.add_argument("--json", action="store_true")
    return parser.parse_args()


def main():
    args = parse_args()

    try:
        from pdf2image import convert_from_path, pdfinfo_from_path
        import pytesseract
        from pytesseract import Output

        if not os.path.isfile(args.input_pdf):
            raise RuntimeError("The source PDF does not exist.")

        if args.tesseract:
            if not os.path.isfile(args.tesseract):
                raise RuntimeError("The configured Tesseract executable does not exist.")
            pytesseract.pytesseract.tesseract_cmd = args.tesseract

        poppler_path = args.poppler or None
        if poppler_path and not os.path.isdir(poppler_path):
            raise RuntimeError("The configured Poppler directory does not exist.")

        info = pdfinfo_from_path(args.input_pdf, poppler_path=poppler_path)
        page_count = int(info.get("Pages", 0))
        if page_count < 1:
            raise RuntimeError("The PDF contains no pages.")
        if page_count > args.max_pages:
            raise RuntimeError(
                f"The PDF contains {page_count} pages; the OCR limit is {args.max_pages}."
            )

        os.makedirs(os.path.dirname(os.path.abspath(args.output_text)), exist_ok=True)
        with open(args.output_text, "w", encoding="utf-8", newline="\n") as output:
            pages_with_text = 0
            rotated_pages = []
            for page_number in range(1, page_count + 1):
                images = convert_from_path(
                    args.input_pdf,
                    dpi=args.dpi,
                    first_page=page_number,
                    last_page=page_number,
                    fmt="png",
                    thread_count=1,
                    poppler_path=poppler_path,
                )
                if not images:
                    raise RuntimeError(f"Poppler did not render page {page_number}.")

                page_image = images[0]
                try:
                    try:
                        orientation = pytesseract.image_to_osd(page_image, output_type=Output.DICT)
                        rotation = int(orientation.get("rotate", 0) or 0)
                    except Exception:
                        rotation = 0

                    if rotation:
                        corrected_image = page_image.rotate(-rotation, expand=True)
                        rotated_pages.append({"page": page_number, "degrees": rotation})
                    else:
                        corrected_image = page_image

                    try:
                        page_text = pytesseract.image_to_string(corrected_image, lang=args.language).strip()
                    finally:
                        if corrected_image is not page_image:
                            corrected_image.close()
                finally:
                    for image in images:
                        image.close()

                if page_text:
                    pages_with_text += 1
                output.write(f"--- Page {page_number} ---\n{page_text}\n")
                if page_number < page_count:
                    output.write("\n")

        if pages_with_text == 0:
            raise RuntimeError("OCR completed, but no searchable text was found.")

        emit(
            {
                "ok": True,
                "pages": page_count,
                "pages_with_text": pages_with_text,
                "rotated_pages": rotated_pages,
            },
            args.json,
        )
        return 0
    except Exception as error:
        try:
            if os.path.isfile(args.output_text):
                os.remove(args.output_text)
        except OSError:
            pass
        emit({"ok": False, "error": str(error)}, args.json)
        if not args.json:
            print(str(error), file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
