import { formatText } from "@/Utils/Common";

const LOG_HEADER_REGEX = /^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] ([\w.]+): (.*)$/;
const LOG_BLOCK_START_REGEX = /^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/gm;
const TRACE_LINE_REGEX = /^#\d+\s/;

function parseLogBlock(block, index) {
    const lines = block.split("\n");
    const headerMatch = lines[0].match(LOG_HEADER_REGEX);

    if( !headerMatch ) return {
        datetime: null,
        type: null,
        level: null,
        message: block.trim(),
        trace: [],
    };

    const [, datetime, type, firstMessage] = headerMatch;
    const messageLines = [];
    const trace = [];
    let inTrace = false;

    for( let i = 1; i < lines.length; i++ ) {
        const line = lines[i];

        if( line === "[stacktrace]" ) {
            inTrace = true;
            continue;
        }

        if( TRACE_LINE_REGEX.test(line) ) {
            inTrace = true;
            trace.push(line);
            continue;
        }

        if( inTrace ) {
            trace.push(line);
            continue;
        }

        messageLines.push(line);
    }

    const level = type.includes(".")
        ? type.split(".").at(-1)
        : type;

    const forSearch = formatText(block);

    return {
        id: `${index}-${datetime || "unknown"}`,
        index,
        datetime,
        type,
        level,
        message: [firstMessage, ...messageLines].join("\n").trim(),
        trace,
        forSearch,
    };
}

export function parseLogs(content) {
    if( !content?.trim() ) {
        return [];
    }

    const starts = [
        ...content.matchAll(LOG_BLOCK_START_REGEX),
    ];

    if( starts.length === 0 ) {
        return [];
    }

    return starts.map((match, index) => {
        const start = match.index;

        const end = index + 1 < starts.length
            ? starts[index + 1].index
            : content.length;

        return parseLogBlock(content.slice(start, end).trim(), index);
    });
}

export function logLevelColor(level) {
    const colors = {
        ERROR: "error",
        CRITICAL: "error",
        EMERGENCY: "error",
        WARNING: "warning",
        ALERT: "warning",
        NOTICE: "info",
        INFO: "info",
        DEBUG: "grey",
    };

    return colors[level?.toUpperCase()] || "grey";
}
