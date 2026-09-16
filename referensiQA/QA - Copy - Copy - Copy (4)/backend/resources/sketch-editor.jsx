import { useState, useRef, useEffect, useCallback } from "react";

const COLORS = {
  red: "#DC2626", blue: "#2563EB", green: "#16A34A",
  amber: "#D97706", purple: "#7C3AED", slate: "#475569"
};

const TOOLS = ["select", "rect", "circle", "text", "zone", "line", "arrow", "image", "delete"];

function generateId() {
  return Math.random().toString(36).slice(2, 9);
}

export default function SketchEditor() {
  const canvasRef = useRef(null);
  const fileInputRef = useRef(null);
  const bgFileRef = useRef(null);
  const [tool, setTool] = useState("select");
  const [color, setColor] = useState("#DC2626");
  const [shapes, setShapes] = useState([]);
  const [selected, setSelected] = useState(null);
  const [drawing, setDrawing] = useState(null);
  const [dragState, setDragState] = useState(null);
  const [resizeState, setResizeState] = useState(null);
  const [connState, setConnState] = useState(null); // connection arrow drawing
  const [bgImage, setBgImage] = useState(null);
  const [editingText, setEditingText] = useState(null);
  const [textVal, setTextVal] = useState("");
  const [hoverPort, setHoverPort] = useState(null); // {shapeId, port}
  const [zoneCount, setZoneCount] = useState(0);
  const canvasSize = { w: 900, h: 580 };

  // Get connection ports for a shape
  function getPorts(s) {
    if (s.type === "circle") {
      const cx = s.x + s.w / 2, cy = s.y + s.h / 2;
      return {
        top: { x: cx, y: s.y },
        right: { x: s.x + s.w, y: cy },
        bottom: { x: cx, y: s.y + s.h },
        left: { x: s.x, y: cy },
      };
    }
    return {
      top: { x: s.x + s.w / 2, y: s.y },
      right: { x: s.x + s.w, y: s.y + s.h / 2 },
      bottom: { x: s.x + s.w / 2, y: s.y + s.h },
      left: { x: s.x, y: s.y + s.h / 2 },
    };
  }

  function getPortNear(pt, excludeId = null) {
    for (const s of shapes) {
      if (s.id === excludeId || (s.type === "arrow" || s.type === "line")) continue;
      const ports = getPorts(s);
      for (const [name, pos] of Object.entries(ports)) {
        const dx = pt.x - pos.x, dy = pt.y - pos.y;
        if (Math.sqrt(dx * dx + dy * dy) < 14) {
          return { shapeId: s.id, port: name, x: pos.x, y: pos.y };
        }
      }
    }
    return null;
  }

  function getSvgPt(e) {
    const svg = canvasRef.current;
    const rect = svg.getBoundingClientRect();
    const scaleX = canvasSize.w / rect.width;
    const scaleY = canvasSize.h / rect.height;
    return {
      x: (e.clientX - rect.left) * scaleX,
      y: (e.clientY - rect.top) * scaleY,
    };
  }

  function resolveConnPt(conn) {
    // For arrows stored as connections
    if (conn.fromId) {
      const s = shapes.find(x => x.id === conn.fromId);
      if (s) return getPorts(s)[conn.fromPort] || { x: conn.x1, y: conn.y1 };
    }
    return { x: conn.x1, y: conn.y1 };
  }

  function resolveConnEnd(conn) {
    if (conn.toId) {
      const s = shapes.find(x => x.id === conn.toId);
      if (s) return getPorts(s)[conn.toPort] || { x: conn.x2, y: conn.y2 };
    }
    return { x: conn.x2, y: conn.y2 };
  }

  // ===== MOUSE DOWN =====
  function onMouseDown(e) {
    if (e.button !== 0) return;
    const pt = getSvgPt(e);

    if (tool === "select" || tool === "delete") {
      // Check if clicking a port for connection
      const port = getPortNear(pt);
      if (port && tool === "select") {
        // Start drawing a connection arrow
        setConnState({ fromId: port.shapeId, fromPort: port.port, x1: port.x, y1: port.y, x2: pt.x, y2: pt.y });
        return;
      }

      // Hit test shapes (reverse for z-order)
      let hit = null;
      for (let i = shapes.length - 1; i >= 0; i--) {
        const s = shapes[i];
        if (hitTest(s, pt)) { hit = s; break; }
      }

      if (tool === "delete" && hit) {
        setShapes(prev => prev.filter(s => s.id !== hit.id && s.fromId !== hit.id && s.toId !== hit.id));
        setSelected(null);
        return;
      }

      if (hit) {
        if (tool === "select") {
          setSelected(hit.id);
          if (hit.type === "text") {
            setEditingText(hit.id);
            setTextVal(hit.label || "");
          }
          setDragState({ id: hit.id, ox: pt.x - hit.x, oy: pt.y - hit.y });
        }
      } else {
        setSelected(null);
        setEditingText(null);
      }
      return;
    }

    if (tool === "text") {
      const id = generateId();
      const s = { id, type: "text", x: pt.x, y: pt.y, w: 120, h: 32, label: "Teks", color, fontSize: 14 };
      setShapes(prev => [...prev, s]);
      setSelected(id);
      setEditingText(id);
      setTextVal("Teks");
      setTool("select");
      return;
    }

    if (tool === "zone") {
      const n = zoneCount + 1;
      setZoneCount(n);
      setDrawing({ type: "zone", x1: pt.x, y1: pt.y, x2: pt.x, y2: pt.y, label: String(n), color });
      return;
    }

    if (["rect", "circle", "line", "arrow"].includes(tool)) {
      setDrawing({ type: tool, x1: pt.x, y1: pt.y, x2: pt.x, y2: pt.y, color });
    }
  }

  function onMouseMove(e) {
    const pt = getSvgPt(e);

    // Hover port detection
    if (tool === "select") {
      const p = getPortNear(pt);
      setHoverPort(p || null);
    }

    if (connState) {
      const port = getPortNear(pt, connState.fromId);
      setConnState(prev => ({ ...prev, x2: port ? port.x : pt.x, y2: port ? port.y : pt.y, toSnap: port }));
      return;
    }

    if (drawing) {
      setDrawing(prev => ({ ...prev, x2: pt.x, y2: pt.y }));
      return;
    }

    if (dragState) {
      setShapes(prev => prev.map(s =>
        s.id === dragState.id
          ? { ...s, x: pt.x - dragState.ox, y: pt.y - dragState.oy }
          : s
      ));
    }
  }

  function onMouseUp(e) {
    const pt = getSvgPt(e);

    if (connState) {
      const port = getPortNear(pt, connState.fromId);
      const id = generateId();
      const arrow = {
        id, type: "arrow",
        x: 0, y: 0, w: 0, h: 0,
        x1: connState.x1, y1: connState.y1,
        x2: port ? port.x : pt.x, y2: port ? port.y : pt.y,
        fromId: connState.fromId, fromPort: connState.fromPort,
        toId: port ? port.shapeId : null, toPort: port ? port.port : null,
        color
      };
      setShapes(prev => [...prev, arrow]);
      setConnState(null);
      return;
    }

    if (drawing) {
      const { type, x1, y1, x2, y2, label, color: c } = drawing;
      const id = generateId();
      if (type === "line" || type === "arrow") {
        const dx = x2 - x1, dy = y2 - y1;
        if (Math.sqrt(dx * dx + dy * dy) > 5) {
          setShapes(prev => [...prev, { id, type, x: 0, y: 0, w: 0, h: 0, x1, y1, x2, y2, color: c }]);
        }
      } else {
        const x = Math.min(x1, x2), y = Math.min(y1, y2);
        const w = Math.abs(x2 - x1), h = Math.abs(y2 - y1);
        if (w > 4 && h > 4) {
          setShapes(prev => [...prev, {
            id, type,
            x, y, w, h, color: c,
            label: type === "zone" ? label : "",
            fontSize: type === "zone" ? 20 : 13
          }]);
          setSelected(id);
        }
      }
      setDrawing(null);
      if (tool !== "zone") setTool("select");
    }

    setDragState(null);
  }

  function getTextBounds(s) {
    const lines = (s.label || "").split('\n');
    const fontSize = s.fontSize || 14;
    const h = Math.max(32, lines.length * fontSize * 1.3 + 12);
    const maxLen = Math.max(...lines.map(l => l.length), 4);
    const w = Math.max(120, maxLen * fontSize * 0.6 + 18);
    return { w, h };
  }

  function hitTest(s, pt) {
    if (s.type === "arrow" || s.type === "line") {
      // Hit test line segment
      const dx = s.x2 - s.x1, dy = s.y2 - s.y1;
      const len = Math.sqrt(dx * dx + dy * dy);
      if (len === 0) return false;
      const t = ((pt.x - s.x1) * dx + (pt.y - s.y1) * dy) / (len * len);
      const tc = Math.max(0, Math.min(1, t));
      const nearX = s.x1 + tc * dx, nearY = s.y1 + tc * dy;
      return Math.sqrt((pt.x - nearX) ** 2 + (pt.y - nearY) ** 2) < 8;
    }
    const w = s.type === "text" ? getTextBounds(s).w : s.w;
    const h = s.type === "text" ? getTextBounds(s).h : s.h;
    return pt.x >= s.x && pt.x <= s.x + w && pt.y >= s.y && pt.y <= s.y + h;
  }

  function handleBgUpload(e) {
    const f = e.target.files[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = ev => setBgImage(ev.target.result);
    reader.readAsDataURL(f);
    e.target.value = "";
  }

  function handleImageShape(e) {
    const f = e.target.files[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = ev => {
      const id = generateId();
      setShapes(prev => [...prev, {
        id, type: "image", x: 50, y: 50, w: 200, h: 150,
        src: ev.target.result, color: "#000"
      }]);
      setSelected(id);
      setTool("select");
    };
    reader.readAsDataURL(f);
    e.target.value = "";
  }

  function updateLabel(id, val) {
    setShapes(prev => prev.map(s => s.id === id ? { ...s, label: val } : s));
  }

  function renderDrawing() {
    if (!drawing) return null;
    const { type, x1, y1, x2, y2, color: c, label } = drawing;
    if (type === "line") {
      return <line x1={x1} y1={y1} x2={x2} y2={y2} stroke={c} strokeWidth={2} strokeDasharray="5,3" />;
    }
    if (type === "arrow") {
      return <line x1={x1} y1={y1} x2={x2} y2={y2} stroke={c} strokeWidth={2} markerEnd="url(#arrowHead)" strokeDasharray="5,3" />;
    }
    const x = Math.min(x1, x2), y = Math.min(y1, y2);
    const w = Math.abs(x2 - x1), h = Math.abs(y2 - y1);
    if (type === "rect") return <rect x={x} y={y} width={w} height={h} fill={c + "22"} stroke={c} strokeWidth={2} strokeDasharray="5,3" rx={3} />;
    if (type === "circle") return <ellipse cx={x + w / 2} cy={y + h / 2} rx={w / 2} ry={h / 2} fill={c + "22"} stroke={c} strokeWidth={2} strokeDasharray="5,3" />;
    if (type === "zone") return (
      <g>
        <rect x={x} y={y} width={w} height={h} fill={c + "18"} stroke={c} strokeWidth={2} strokeDasharray="6,3" rx={4} />
        <text x={x + 10} y={y + 28} fontSize={22} fontWeight="900" fill={c} opacity={0.8}>{label}</text>
      </g>
    );
    return null;
  }

  function renderConnDrawing() {
    if (!connState) return null;
    return <line x1={connState.x1} y1={connState.y1} x2={connState.x2} y2={connState.y2}
      stroke="#3B82F6" strokeWidth={2} strokeDasharray="5,3" markerEnd="url(#arrowHeadBlue)" />;
  }

  function renderShape(s) {
    const isSelected = selected === s.id;
    const selStyle = isSelected ? { filter: "drop-shadow(0 0 4px rgba(59,130,246,0.8))" } : {};

    if (s.type === "image") {
      return (
        <g key={s.id} style={selStyle}>
          <image href={s.src} x={s.x} y={s.y} width={s.w} height={s.h} preserveAspectRatio="xMidYMid meet" />
          {isSelected && renderHandles(s)}
          {renderPorts(s)}
        </g>
      );
    }

    if (s.type === "arrow" || s.type === "line") {
      // Resolve endpoints (may be connected to shapes)
      let x1 = s.x1, y1 = s.y1, x2 = s.x2, y2 = s.y2;
      if (s.fromId) {
        const src = shapes.find(x => x.id === s.fromId);
        if (src) { const p = getPorts(src)[s.fromPort]; if (p) { x1 = p.x; y1 = p.y; } }
      }
      if (s.toId) {
        const tgt = shapes.find(x => x.id === s.toId);
        if (tgt) { const p = getPorts(tgt)[s.toPort]; if (p) { x2 = p.x; y2 = p.y; } }
      }
      return (
        <g key={s.id} style={selStyle}>
          <line x1={x1} y1={y1} x2={x2} y2={y2} stroke={s.color} strokeWidth={isSelected ? 3 : 2}
            markerEnd={s.type === "arrow" ? `url(#arrowHead_${s.id})` : undefined} />
          {/* Invisible hit area */}
          <line x1={x1} y1={y1} x2={x2} y2={y2} stroke="transparent" strokeWidth={12} />
          <defs>
            <marker id={`arrowHead_${s.id}`} markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
              <polygon points="0 0, 10 3.5, 0 7" fill={s.color} />
            </marker>
          </defs>
        </g>
      );
    }

    if (s.type === "text") {
      const bounds = getTextBounds(s);
      return (
        <g key={s.id} style={{ ...selStyle, cursor: tool === "select" ? "move" : "default" }}>
          {editingText === s.id
            ? <foreignObject x={s.x} y={s.y} width={bounds.w} height={bounds.h + 20}>
              <textarea
                style={{
                  width: "100%",
                  height: "100%",
                  background: "#fff",
                  border: "2px solid #3B82F6",
                  borderRadius: 4,
                  padding: "4px 6px",
                  fontSize: s.fontSize || 14,
                  fontWeight: 700,
                  color: s.color,
                  outline: "none",
                  resize: "none",
                  fontFamily: "inherit",
                  lineHeight: 1.3
                }}
                value={textVal}
                autoFocus
                onChange={ev => { setTextVal(ev.target.value); updateLabel(s.id, ev.target.value); }}
                onBlur={() => setEditingText(null)}
                onKeyDown={ev => {
                  if (ev.key === "Enter" && !ev.shiftKey) {
                    ev.preventDefault();
                    setEditingText(null);
                  }
                }}
              />
            </foreignObject>
            : <text x={s.x} y={s.y} fontSize={s.fontSize || 14} fontWeight="700" fill={s.color}>
                {(s.label || "").split('\n').map((line, idx) => (
                  <tspan key={idx} x={s.x} dy={idx === 0 ? (s.fontSize || 14) + 2 : (s.fontSize || 14) * 1.3} xmlSpace="preserve">
                    {line}
                  </tspan>
                ))}
              </text>
          }
          {isSelected && <rect x={s.x - 4} y={s.y - 2} width={bounds.w + 8} height={bounds.h + 4}
            fill="none" stroke="#3B82F6" strokeWidth={1.5} strokeDasharray="4,2" rx={3} />}
        </g>
      );
    }

    if (s.type === "rect" || s.type === "zone") {
      return (
        <g key={s.id} style={selStyle}>
          <rect x={s.x} y={s.y} width={s.w} height={s.h}
            fill={s.type === "zone" ? s.color + "18" : s.color + "22"}
            stroke={s.color} strokeWidth={s.type === "zone" ? 2.5 : 2} rx={s.type === "zone" ? 4 : 3} />
          {s.label && <text x={s.x + 10} y={s.y + (s.fontSize || 13) + 8} fontSize={s.type === "zone" ? 22 : 12}
            fontWeight="900" fill={s.color} opacity={0.9}>{s.label}</text>}
          {isSelected && renderHandles(s)}
          {renderPorts(s)}
        </g>
      );
    }

    if (s.type === "circle") {
      return (
        <g key={s.id} style={selStyle}>
          <ellipse cx={s.x + s.w / 2} cy={s.y + s.h / 2} rx={s.w / 2} ry={s.h / 2}
            fill={s.color + "22"} stroke={s.color} strokeWidth={2} />
          {s.label && <text x={s.x + s.w / 2} y={s.y + s.h / 2 + 5} fontSize={12}
            fontWeight="700" fill={s.color} textAnchor="middle">{s.label}</text>}
          {isSelected && renderHandles(s)}
          {renderPorts(s)}
        </g>
      );
    }

    return null;
  }

  function renderHandles(s) {
    const handles = [
      { cx: s.x, cy: s.y }, { cx: s.x + s.w / 2, cy: s.y },
      { cx: s.x + s.w, cy: s.y }, { cx: s.x + s.w, cy: s.y + s.h / 2 },
      { cx: s.x + s.w, cy: s.y + s.h }, { cx: s.x + s.w / 2, cy: s.y + s.h },
      { cx: s.x, cy: s.y + s.h }, { cx: s.x, cy: s.y + s.h / 2 },
    ];
    return handles.map((h, i) => (
      <rect key={i} x={h.cx - 4} y={h.cy - 4} width={8} height={8}
        fill="#fff" stroke="#3B82F6" strokeWidth={1.5} rx={1} style={{ cursor: "se-resize" }} />
    ));
  }

  function renderPorts(s) {
    const ports = getPorts(s);
    return Object.entries(ports).map(([name, pos]) => {
      const isHovered = hoverPort && hoverPort.shapeId === s.id && hoverPort.port === name;
      return (
        <circle key={name} cx={pos.x} cy={pos.y} r={isHovered ? 7 : 5}
          fill={isHovered ? "#3B82F6" : "#fff"} stroke="#3B82F6" strokeWidth={2}
          style={{ cursor: "crosshair", opacity: isHovered ? 1 : 0.4, transition: "all 0.1s" }} />
      );
    });
  }

  const toolConfig = [
    { id: "select", icon: "⬆", label: "Pilih" },
    { id: "rect", icon: "⬜", label: "Kotak" },
    { id: "circle", icon: "⭕", label: "Lingkaran" },
    { id: "zone", icon: "🔲", label: "Zona" },
    { id: "text", icon: "T", label: "Teks" },
    { id: "line", icon: "╱", label: "Garis" },
    { id: "arrow", icon: "→", label: "Panah" },
    { id: "delete", icon: "🗑", label: "Hapus" },
  ];

  function exportSVG() {
    const svg = canvasRef.current;
    const data = new XMLSerializer().serializeToString(svg);
    const blob = new Blob([data], { type: "image/svg+xml" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url; a.download = "sketch-part.svg"; a.click();
    URL.revokeObjectURL(url);
  }

  function clearAll() {
    if (confirm("Hapus semua elemen di canvas?")) {
      setShapes([]); setSelected(null); setZoneCount(0);
    }
  }

  const selShape = shapes.find(s => s.id === selected);

  return (
    <div style={{ fontFamily: "system-ui, sans-serif", background: "#0F172A", minHeight: "100vh", display: "flex", flexDirection: "column", color: "#E2E8F0" }}>
      {/* HEADER */}
      <div style={{ background: "#1E293B", borderBottom: "1px solid #334155", padding: "10px 16px", display: "flex", alignItems: "center", justifyContent: "space-between", gap: 12, flexWrap: "wrap" }}>
        <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
          <div style={{ width: 32, height: 32, background: "#DC2626", borderRadius: 8, display: "flex", alignItems: "center", justifyContent: "center", fontWeight: 900, color: "#fff", fontSize: 16 }}>Q</div>
          <div>
            <div style={{ fontSize: 13, fontWeight: 800, color: "#F8FAFC", letterSpacing: 1 }}>SKETCH PART EDITOR</div>
            <div style={{ fontSize: 10, color: "#64748B", fontWeight: 700, letterSpacing: 2 }}>VISUAL DEFECT MAPPING</div>
          </div>
        </div>

        {/* COLOR PICKER */}
        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <span style={{ fontSize: 10, color: "#64748B", fontWeight: 700 }}>WARNA:</span>
          {Object.values(COLORS).map(c => (
            <button key={c} onClick={() => setColor(c)}
              style={{ width: 22, height: 22, borderRadius: "50%", background: c, border: color === c ? "3px solid #fff" : "2px solid transparent", cursor: "pointer", transition: "all 0.1s" }} />
          ))}
          <input type="color" value={color} onChange={e => setColor(e.target.value)}
            style={{ width: 28, height: 28, borderRadius: 6, border: "2px solid #334155", cursor: "pointer", background: "none", padding: 2 }} />
        </div>

        {/* ACTIONS */}
        <div style={{ display: "flex", gap: 8 }}>
          <button onClick={() => bgFileRef.current.click()}
            style={{ padding: "6px 14px", background: "#1D4ED8", color: "#fff", border: "none", borderRadius: 8, fontSize: 11, fontWeight: 700, cursor: "pointer" }}>
            🖼 Upload Foto Part
          </button>
          <button onClick={() => fileInputRef.current.click()}
            style={{ padding: "6px 14px", background: "#1E293B", color: "#94A3B8", border: "1px solid #334155", borderRadius: 8, fontSize: 11, fontWeight: 700, cursor: "pointer" }}>
            + Gambar
          </button>
          <button onClick={exportSVG}
            style={{ padding: "6px 14px", background: "#16A34A", color: "#fff", border: "none", borderRadius: 8, fontSize: 11, fontWeight: 700, cursor: "pointer" }}>
            💾 Export SVG
          </button>
          <button onClick={clearAll}
            style={{ padding: "6px 14px", background: "#7F1D1D", color: "#FCA5A5", border: "none", borderRadius: 8, fontSize: 11, fontWeight: 700, cursor: "pointer" }}>
            🗑 Bersihkan
          </button>
        </div>

        <input type="file" ref={bgFileRef} accept="image/*" style={{ display: "none" }} onChange={handleBgUpload} />
        <input type="file" ref={fileInputRef} accept="image/*" style={{ display: "none" }} onChange={handleImageShape} />
      </div>

      <div style={{ display: "flex", flex: 1 }}>
        {/* TOOLBAR */}
        <div style={{ width: 68, background: "#1E293B", borderRight: "1px solid #334155", display: "flex", flexDirection: "column", alignItems: "center", padding: "12px 0", gap: 6 }}>
          {toolConfig.map(t => (
            <button key={t.id} onClick={() => setTool(t.id)} title={t.label}
              style={{
                width: 48, height: 48, display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", gap: 2,
                background: tool === t.id ? "#3B82F6" : "transparent",
                border: tool === t.id ? "none" : "1px solid #334155",
                borderRadius: 10, cursor: "pointer", color: tool === t.id ? "#fff" : "#64748B",
                fontSize: t.id === "text" ? 18 : 20, fontWeight: 900, transition: "all 0.15s",
              }}>
              <span>{t.icon}</span>
              <span style={{ fontSize: 8, fontWeight: 700, letterSpacing: 0.5 }}>{t.label.toUpperCase()}</span>
            </button>
          ))}

          <div style={{ width: 40, height: 1, background: "#334155", margin: "4px 0" }} />

          {/* Tips */}
          <div style={{ padding: "0 8px", textAlign: "center" }}>
            <div style={{ fontSize: 9, color: "#475569", lineHeight: 1.5, fontWeight: 600 }}>
              Drag port biru → untuk buat panah koneksi
            </div>
          </div>
        </div>

        {/* CANVAS AREA */}
        <div style={{ flex: 1, display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", background: "#0F172A", overflow: "auto", padding: 20 }}>
          {/* Legend */}
          <div style={{ marginBottom: 10, display: "flex", gap: 16, alignItems: "center", flexWrap: "wrap" }}>
            {[["✓ OK", "#16A34A"], ["✕ NG", "#DC2626"], ["▲ CORRECT", "#2563EB"], ["↳ REWORK", "#D97706"]].map(([l, c]) => (
              <div key={l} style={{ display: "flex", alignItems: "center", gap: 5, fontSize: 10, fontWeight: 700, color: c }}>
                <div style={{ width: 8, height: 8, borderRadius: "50%", background: c }} />
                {l}
              </div>
            ))}
            <div style={{ fontSize: 10, color: "#475569", fontWeight: 700 }}>| Zona: gunakan tool 🔲 lalu nomor otomatis</div>
          </div>

          <svg
            ref={canvasRef}
            width={canvasSize.w}
            height={canvasSize.h}
            style={{
              background: "#fff", borderRadius: 12, boxShadow: "0 0 0 1px #334155, 0 20px 60px rgba(0,0,0,0.5)",
              cursor: tool === "select" ? (connState ? "crosshair" : "default") : tool === "delete" ? "not-allowed" : "crosshair",
              userSelect: "none", maxWidth: "100%"
            }}
            onMouseDown={onMouseDown}
            onMouseMove={onMouseMove}
            onMouseUp={onMouseUp}
            onMouseLeave={() => { setDragState(null); if (connState) setConnState(null); if (drawing) setDrawing(null); setHoverPort(null); }}
          >
            <defs>
              <marker id="arrowHead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                <polygon points="0 0, 10 3.5, 0 7" fill={color} />
              </marker>
              <marker id="arrowHeadBlue" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                <polygon points="0 0, 10 3.5, 0 7" fill="#3B82F6" />
              </marker>
            </defs>

            {/* Grid dots */}
            <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
              <circle cx="0" cy="0" r="0.8" fill="#E2E8F0" opacity="0.4" />
            </pattern>
            <rect width={canvasSize.w} height={canvasSize.h} fill="url(#grid)" />

            {/* Background image */}
            {bgImage && <image href={bgImage} x={0} y={0} width={canvasSize.w} height={canvasSize.h} preserveAspectRatio="xMidYMid meet" opacity={0.85} />}

            {/* Shapes */}
            {shapes.map(s => renderShape(s))}

            {/* Active drawing preview */}
            {renderDrawing()}
            {renderConnDrawing()}
          </svg>

          {/* Properties panel */}
          {selShape && !["arrow", "line"].includes(selShape.type) && (
            <div style={{ marginTop: 12, background: "#1E293B", border: "1px solid #334155", borderRadius: 10, padding: "10px 16px", display: "flex", alignItems: "center", gap: 14, flexWrap: "wrap" }}>
              <span style={{ fontSize: 10, fontWeight: 700, color: "#64748B", textTransform: "uppercase" }}>Properti Objek:</span>
              <label style={{ fontSize: 11, color: "#94A3B8", fontWeight: 600, display: "flex", alignItems: "center", gap: 6 }}>
                Label:
                <input value={selShape.label || ""} onChange={e => setShapes(prev => prev.map(s => s.id === selShape.id ? { ...s, label: e.target.value } : s))}
                  style={{ background: "#0F172A", border: "1px solid #334155", borderRadius: 6, padding: "3px 8px", color: "#E2E8F0", fontSize: 12, width: 100 }} />
              </label>
              <label style={{ fontSize: 11, color: "#94A3B8", fontWeight: 600, display: "flex", alignItems: "center", gap: 6 }}>
                W:
                <input type="number" value={Math.round(selShape.w)} onChange={e => setShapes(prev => prev.map(s => s.id === selShape.id ? { ...s, w: +e.target.value } : s))}
                  style={{ background: "#0F172A", border: "1px solid #334155", borderRadius: 6, padding: "3px 8px", color: "#E2E8F0", fontSize: 12, width: 60 }} />
              </label>
              <label style={{ fontSize: 11, color: "#94A3B8", fontWeight: 600, display: "flex", alignItems: "center", gap: 6 }}>
                H:
                <input type="number" value={Math.round(selShape.h)} onChange={e => setShapes(prev => prev.map(s => s.id === selShape.id ? { ...s, h: +e.target.value } : s))}
                  style={{ background: "#0F172A", border: "1px solid #334155", borderRadius: 6, padding: "3px 8px", color: "#E2E8F0", fontSize: 12, width: 60 }} />
              </label>
              <button onClick={() => { setShapes(prev => prev.filter(s => s.id !== selShape.id)); setSelected(null); }}
                style={{ padding: "4px 12px", background: "#7F1D1D", color: "#FCA5A5", border: "none", borderRadius: 6, fontSize: 11, fontWeight: 700, cursor: "pointer" }}>
                🗑 Hapus
              </button>
            </div>
          )}

          <div style={{ marginTop: 10, fontSize: 10, color: "#475569", fontWeight: 600, textAlign: "center", lineHeight: 1.8 }}>
            Klik shape → titik biru muncul di tepi → drag titik biru untuk buat panah koneksi &nbsp;|&nbsp; Klik 2x teks untuk edit &nbsp;|&nbsp; Tool Zona: nomor urut otomatis
          </div>
        </div>
      </div>
    </div>
  );
}
