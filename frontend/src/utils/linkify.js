/**
 * Trims trailing punctuation or unmatched closing brackets from matched URLs.
 * Handles cases like:
 * - "Visit https://example.com." -> URL: "https://example.com", Trailing: "."
 * - "(see https://example.com)" -> URL: "https://example.com", Trailing: ")"
 * - "https://en.wikipedia.org/wiki/React_(software)" -> URL preserved with balanced parentheses
 */
function trimTrailingPunctuation(rawUrl) {
  let url = rawUrl
  let trailing = ''
  const punctRegex = /[.,;:!?'"<>]+$/

  while (url.length > 0) {
    const punctMatch = url.match(punctRegex)
    if (punctMatch) {
      trailing = punctMatch[0] + trailing
      url = url.slice(0, -punctMatch[0].length)
      continue
    }

    // Handle unmatched closing parenthesis
    if (url.endsWith(')')) {
      const openCount = (url.match(/\(/g) || []).length
      const closeCount = (url.match(/\)/g) || []).length
      if (closeCount > openCount) {
        trailing = ')' + trailing
        url = url.slice(0, -1)
        continue
      }
    }

    // Handle unmatched closing square bracket
    if (url.endsWith(']')) {
      const openCount = (url.match(/\[/g) || []).length
      const closeCount = (url.match(/\]/g) || []).length
      if (closeCount > openCount) {
        trailing = ']' + trailing
        url = url.slice(0, -1)
        continue
      }
    }

    // Handle unmatched closing curly brace
    if (url.endsWith('}')) {
      const openCount = (url.match(/\{/g) || []).length
      const closeCount = (url.match(/\}/g) || []).length
      if (closeCount > openCount) {
        trailing = '}' + trailing
        url = url.slice(0, -1)
        continue
      }
    }

    break
  }

  return { url, trailing }
}

/**
 * Validates the candidate URL and returns a safe href string, or null if invalid.
 * Ensures only safe protocols (http, https, ftp) are permitted.
 */
function getValidHref(url) {
  if (!url) return null

  // URLs starting with http://, https://, or ftp://
  if (/^(?:https?|ftp):\/\//i.test(url)) {
    try {
      const parsed = new URL(url)
      if (['http:', 'https:', 'ftp:'].includes(parsed.protocol)) {
        return url
      }
    } catch {
      return null
    }
  }

  // URLs starting with www. (auto-prepend https://)
  if (/^www\.[a-z0-9-]+(\.[a-z0-9-]+)+/i.test(url)) {
    try {
      const parsed = new URL(`https://${url}`)
      if (parsed.protocol === 'https:') {
        return `https://${url}`
      }
    } catch {
      return null
    }
  }

  return null
}

/**
 * Parses message text into an array of segments (text and link).
 *
 * @param {string} text - Raw message text body
 * @returns {Array<{type: 'text'|'link', text: string, href?: string}>}
 */
export function parseMessageText(text) {
  if (!text) return []

  const urlPattern = /\b(?:(?:https?|ftp):\/\/[^\s<>"']+|www\.[a-z0-9\-]+[^\s<>"']*)/gi
  const segments = []
  let lastIndex = 0
  let match

  function pushSegment(type, str, href = null) {
    if (!str) return
    const last = segments[segments.length - 1]
    if (type === 'text' && last && last.type === 'text') {
      last.text += str
    } else {
      segments.push(href ? { type, text: str, href } : { type, text: str })
    }
  }

  while ((match = urlPattern.exec(text)) !== null) {
    const matchIndex = match.index
    const fullMatch = match[0]

    // Preceding plain text
    if (matchIndex > lastIndex) {
      pushSegment('text', text.slice(lastIndex, matchIndex))
    }

    const { url, trailing } = trimTrailingPunctuation(fullMatch)
    const href = getValidHref(url)

    if (url && href) {
      pushSegment('link', url, href)
    } else if (url) {
      pushSegment('text', url)
    }

    if (trailing) {
      pushSegment('text', trailing)
    }

    lastIndex = matchIndex + fullMatch.length
  }

  // Trailing plain text
  if (lastIndex < text.length) {
    pushSegment('text', text.slice(lastIndex))
  }

  return segments
}
