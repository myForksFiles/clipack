#!/usr/bin/env python3

import argparse
import os
import sys
from pathlib import Path

from openai import OpenAI


def read_text(path: str) -> str:
    return Path(path).read_text(encoding="utf-8")


def write_text(path: str, text: str) -> None:
    Path(path).parent.mkdir(parents=True, exist_ok=True)
    Path(path).write_text(text, encoding="utf-8")


def main() -> int:
    parser = argparse.ArgumentParser(description="Convert transcript to article using OpenAI Responses API.")
    parser.add_argument("--input", required=True, help="Input transcript TXT file")
    parser.add_argument("--output", required=True, help="Output article markdown file")
    parser.add_argument("--model", default=os.getenv("OPENAI_MODEL", "gpt-5.5"))
    parser.add_argument("--language", default="pl")
    parser.add_argument("--style", default="publicystyczny, konkretny, uporządkowany")
    parser.add_argument("--title", default="")
    args = parser.parse_args()

    transcript = read_text(args.input).strip()

    if not transcript:
        print("Empty transcript", file=sys.stderr)
        return 1

    client = OpenAI(api_key=os.getenv("OPENAI_API_KEY"))

    instructions = (
        "Jesteś redaktorem. Zamieniasz surową transkrypcję w czytelny artykuł. "
        "Nie zmyślaj faktów. Jeśli czegoś nie ma w transkrypcji, nie dodawaj tego jako faktu. "
        "Popraw składnię, usuń powtórzenia, uporządkuj argumentację. "
        "Dodaj tytuł, lead, śródtytuły i krótkie podsumowanie. "
        "Zachowaj sens wypowiedzi."
    )

    user_input = f"""
Język artykułu: {args.language}
Styl: {args.style}
Preferowany tytuł, jeżeli podany: {args.title}

TRANSKRYPCJA:
{transcript}
"""

    response = client.responses.create(
        model=args.model,
        instructions=instructions,
        input=user_input,
    )

    article = response.output_text.strip()

    write_text(args.output, article)

    print(args.output)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
