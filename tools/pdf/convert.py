import argparse
import json
import sys
from pathlib import Path

from pdfrw import PdfReader, PdfWriter


DEFAULT_STAMPABLE_VERSION = "1.4"
SUPPORTED_PDF_VERSIONS = {"1.3", "1.4", "1.5", "1.6", "1.7"}


def convert_to_stampable(input_path, output_path, target_version=DEFAULT_STAMPABLE_VERSION):
    input_pdf = Path(input_path)
    output_pdf = Path(output_path)

    if target_version not in SUPPORTED_PDF_VERSIONS:
        raise ValueError(
            f"Unsupported PDF version '{target_version}'. "
            f"Use one of: {', '.join(sorted(SUPPORTED_PDF_VERSIONS))}."
        )

    if not input_pdf.exists():
        raise FileNotFoundError(f"File not found: {input_pdf}")

    if input_pdf.resolve() == output_pdf.resolve():
        raise ValueError("Input and output paths must be different.")

    if output_pdf.parent:
        output_pdf.parent.mkdir(parents=True, exist_ok=True)

    reader = PdfReader(str(input_pdf))
    writer = PdfWriter()
    writer.trailer = reader
    writer.version = target_version
    writer.write(str(output_pdf))

    return output_pdf


def parse_args(argv):
    parser = argparse.ArgumentParser(
        description=(
            "Rewrite a PDF as a stampable PDF version. "
            "Defaults to PDF 1.4, which is commonly accepted by stamp/e-filing tools."
        )
    )
    parser.add_argument("input_pdf", help="Path to the source PDF.")
    parser.add_argument("output_pdf", help="Path for the converted PDF.")
    parser.add_argument(
        "version",
        nargs="?",
        default=DEFAULT_STAMPABLE_VERSION,
        help=f"Target PDF version. Default: {DEFAULT_STAMPABLE_VERSION}.",
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="Print a JSON response for application integrations.",
    )
    return parser.parse_args(argv)


def main(argv=None):
    args = parse_args(argv or sys.argv[1:])

    try:
        output_pdf = convert_to_stampable(args.input_pdf, args.output_pdf, args.version)
    except Exception as exc:
        if args.json:
            print(
                json.dumps(
                    {
                        "ok": False,
                        "error": str(exc),
                        "input": args.input_pdf,
                        "output": args.output_pdf,
                        "version": args.version,
                    }
                ),
                flush=True,
            )
            return 2

        print(f"Conversion error: {exc}", file=sys.stderr, flush=True)
        return 2

    if args.json:
        print(
            json.dumps(
                {
                    "ok": True,
                    "input": args.input_pdf,
                    "output": str(output_pdf),
                    "version": args.version,
                }
            ),
            flush=True,
        )
        return 0

    print(f"Converted to stampable PDF {args.version}: {output_pdf}", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
