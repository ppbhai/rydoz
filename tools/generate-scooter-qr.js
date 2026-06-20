import fs from 'fs';
import path from 'path';
import zlib from 'zlib';

const text = process.argv[2] || 'scooter:SCOOTER001';
const out = process.argv[3] || 'docs/iot/scooter-SCOOTER001-qr.svg';
const version = 3;
const size = 17 + version * 4;
const eccLength = 15;
const dataLength = 55;
const quiet = 4;
const scale = 12;

function bits(value, length) {
  const result = [];
  for (let i = length - 1; i >= 0; i--) result.push((value >>> i) & 1);
  return result;
}

function gfTables() {
  const exp = new Array(512);
  const log = new Array(256);
  let x = 1;
  for (let i = 0; i < 255; i++) {
    exp[i] = x;
    log[x] = i;
    x <<= 1;
    if (x & 0x100) x ^= 0x11d;
  }
  for (let i = 255; i < 512; i++) exp[i] = exp[i - 255];
  return { exp, log };
}

const { exp, log } = gfTables();

function gfMul(a, b) {
  if (a === 0 || b === 0) return 0;
  return exp[log[a] + log[b]];
}

function rsGenerator(degree) {
  let poly = [1];
  for (let i = 0; i < degree; i++) {
    const next = new Array(poly.length + 1).fill(0);
    for (let j = 0; j < poly.length; j++) {
      next[j] ^= gfMul(poly[j], 1);
      next[j + 1] ^= gfMul(poly[j], exp[i]);
    }
    poly = next;
  }
  return poly;
}

function rsEcc(data, degree) {
  const gen = rsGenerator(degree);
  const ecc = new Array(degree).fill(0);
  for (const byte of data) {
    const factor = byte ^ ecc.shift();
    ecc.push(0);
    for (let i = 0; i < degree; i++) {
      ecc[i] ^= gfMul(gen[i + 1], factor);
    }
  }
  return ecc;
}

function makeCodewords(input) {
  const bytes = Array.from(Buffer.from(input, 'utf8'));
  const stream = [];
  stream.push(...bits(0b0100, 4));
  stream.push(...bits(bytes.length, 8));
  for (const byte of bytes) stream.push(...bits(byte, 8));
  stream.push(...new Array(Math.min(4, dataLength * 8 - stream.length)).fill(0));
  while (stream.length % 8 !== 0) stream.push(0);

  const data = [];
  for (let i = 0; i < stream.length; i += 8) {
    data.push(parseInt(stream.slice(i, i + 8).join(''), 2));
  }
  for (let pad = 0; data.length < dataLength; pad++) data.push(pad % 2 === 0 ? 0xec : 0x11);
  return data.concat(rsEcc(data, eccLength));
}

function matrix() {
  return Array.from({ length: size }, () => Array.from({ length: size }, () => null));
}

function set(m, r, c, value) {
  if (r >= 0 && r < size && c >= 0 && c < size) m[r][c] = value;
}

function finder(m, r, c) {
  for (let y = -1; y <= 7; y++) {
    for (let x = -1; x <= 7; x++) {
      const rr = r + y, cc = c + x;
      if (rr < 0 || rr >= size || cc < 0 || cc >= size) continue;
      if (y === -1 || y === 7 || x === -1 || x === 7) set(m, rr, cc, false);
      else if (y === 0 || y === 6 || x === 0 || x === 6) set(m, rr, cc, true);
      else if (y >= 2 && y <= 4 && x >= 2 && x <= 4) set(m, rr, cc, true);
      else set(m, rr, cc, false);
    }
  }
}

function alignment(m, centerR, centerC) {
  for (let y = -2; y <= 2; y++) {
    for (let x = -2; x <= 2; x++) {
      const d = Math.max(Math.abs(x), Math.abs(y));
      set(m, centerR + y, centerC + x, d !== 1);
    }
  }
}

function reserveFormat(m) {
  for (let i = 0; i < 9; i++) {
    if (i !== 6) {
      set(m, 8, i, false);
      set(m, i, 8, false);
    }
  }
  for (let i = 0; i < 8; i++) {
    set(m, 8, size - 1 - i, false);
    set(m, size - 1 - i, 8, false);
  }
  set(m, size - 8, 8, true);
}

function addPatterns(m) {
  finder(m, 0, 0);
  finder(m, 0, size - 7);
  finder(m, size - 7, 0);
  alignment(m, size - 7, size - 7);
  for (let i = 8; i < size - 8; i++) {
    set(m, 6, i, i % 2 === 0);
    set(m, i, 6, i % 2 === 0);
  }
  reserveFormat(m);
}

function maskBit(mask, r, c) {
  switch (mask) {
    case 0: return (r + c) % 2 === 0;
    case 1: return r % 2 === 0;
    case 2: return c % 3 === 0;
    case 3: return (r + c) % 3 === 0;
    default: return false;
  }
}

function placeData(m, codewords, mask) {
  const dataBits = [];
  for (const byte of codewords) dataBits.push(...bits(byte, 8));
  let index = 0;
  let upward = true;

  for (let c = size - 1; c > 0; c -= 2) {
    if (c === 6) c--;
    for (let i = 0; i < size; i++) {
      const r = upward ? size - 1 - i : i;
      for (let dx = 0; dx < 2; dx++) {
        const cc = c - dx;
        if (m[r][cc] !== null) continue;
        const bit = index < dataBits.length ? dataBits[index++] === 1 : false;
        m[r][cc] = bit ^ maskBit(mask, r, cc);
      }
    }
    upward = !upward;
  }
}

function formatBits(mask) {
  let data = (0b01 << 3) | mask;
  let value = data << 10;
  const generator = 0x537;
  for (let i = 14; i >= 10; i--) {
    if ((value >>> i) & 1) value ^= generator << (i - 10);
  }
  return ((data << 10) | value) ^ 0x5412;
}

function addFormat(m, mask) {
  const f = bits(formatBits(mask), 15);
  const a = [[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
  const b = [[size-1,8],[size-2,8],[size-3,8],[size-4,8],[size-5,8],[size-6,8],[size-7,8],[8,size-8],[8,size-7],[8,size-6],[8,size-5],[8,size-4],[8,size-3],[8,size-2],[8,size-1]];
  for (let i = 0; i < 15; i++) {
    set(m, a[i][0], a[i][1], f[i] === 1);
    set(m, b[i][0], b[i][1], f[i] === 1);
  }
  set(m, size - 8, 8, true);
}

function renderSvg(m) {
  const imageSize = (size + quiet * 2) * scale;
  const rects = [];
  for (let r = 0; r < size; r++) {
    for (let c = 0; c < size; c++) {
      if (m[r][c]) rects.push(`<rect x="${(c + quiet) * scale}" y="${(r + quiet) * scale}" width="${scale}" height="${scale}"/>`);
    }
  }
  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${imageSize}" height="${imageSize}" viewBox="0 0 ${imageSize} ${imageSize}">
  <rect width="100%" height="100%" fill="#fff"/>
  <g fill="#000">
    ${rects.join('\n    ')}
  </g>
</svg>
`;
}

function crc32(buffer) {
  let crc = 0xffffffff;
  for (const byte of buffer) {
    crc ^= byte;
    for (let i = 0; i < 8; i++) crc = (crc >>> 1) ^ (0xedb88320 & -(crc & 1));
  }
  return (crc ^ 0xffffffff) >>> 0;
}

function pngChunk(type, data) {
  const typeBuffer = Buffer.from(type, 'ascii');
  const length = Buffer.alloc(4);
  length.writeUInt32BE(data.length, 0);
  const crc = Buffer.alloc(4);
  crc.writeUInt32BE(crc32(Buffer.concat([typeBuffer, data])), 0);
  return Buffer.concat([length, typeBuffer, data, crc]);
}

function renderPng(m) {
  const imageSize = (size + quiet * 2) * scale;
  const rowLength = imageSize * 3 + 1;
  const raw = Buffer.alloc(rowLength * imageSize);

  for (let y = 0; y < imageSize; y++) {
    const rowStart = y * rowLength;
    raw[rowStart] = 0;
    for (let x = 0; x < imageSize; x++) {
      const qrR = Math.floor(y / scale) - quiet;
      const qrC = Math.floor(x / scale) - quiet;
      const dark = qrR >= 0 && qrR < size && qrC >= 0 && qrC < size && m[qrR][qrC];
      const color = dark ? 0 : 255;
      const offset = rowStart + 1 + x * 3;
      raw[offset] = color;
      raw[offset + 1] = color;
      raw[offset + 2] = color;
    }
  }

  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(imageSize, 0);
  ihdr.writeUInt32BE(imageSize, 4);
  ihdr[8] = 8;
  ihdr[9] = 2;
  ihdr[10] = 0;
  ihdr[11] = 0;
  ihdr[12] = 0;

  return Buffer.concat([
    Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
    pngChunk('IHDR', ihdr),
    pngChunk('IDAT', zlib.deflateSync(raw)),
    pngChunk('IEND', Buffer.alloc(0)),
  ]);
}

const m = matrix();
addPatterns(m);
placeData(m, makeCodewords(text), 0);
addFormat(m, 0);

fs.mkdirSync(path.dirname(out), { recursive: true });
fs.writeFileSync(out, renderSvg(m));
fs.writeFileSync(out.replace(/\.svg$/i, '.png'), renderPng(m));
console.log(`QR created for "${text}" at ${out}`);
