import { QuartzTransformerPlugin } from "../../quartz/plugins/types"
import { JSResource } from "../../quartz/util/resources"

/**
 * Mermaid Self-Host Plugin for Quartz v5
 *
 * Renders Mermaid diagrams from a locally-hosted bundle instead of the
 * cdnjs CDN, which is unreliable in China.
 *
 * How it works:
 * 1. Transformer (build-time): renames `code.mermaid` → `code.mermaid-selfhost`
 *    so the built-in mermaid script (which imports from cdnjs CDN) finds
 *    zero elements and does nothing.
 * 2. externalResources (runtime): injects an inline afterDOMReady script
 *    that loads `static/mermaid.bundle.js` (UMD) and renders all
 *    `code.mermaid-selfhost` elements, with theme support, zoom/pan,
 *    and SPA navigation compatibility.
 *
 * The expand button + modal HTML are already added by @quartz-community/rehype-obsidian.
 * This plugin just changes which script does the rendering.
 *
 * Order must be > 30 (obsidian-flavored-markdown's order) so the rename
 * happens after the expand/modal HTML is injected.
 */
export const MermaidSelfhost: QuartzTransformerPlugin = () => ({
  name: "MermaidSelfhost",
  htmlPlugins() {
    return [
      () => (tree: any) => {
        // Walk the HAST tree and rename code.mermaid → code.mermaid-selfhost
        // This prevents the built-in script's querySelectorAll("code.mermaid") from matching
        const visit = (node: any) => {
          if (!node) return
          if (node.type === "element" && node.tagName === "code") {
            const classNames = node.properties?.className
            if (Array.isArray(classNames) && classNames.includes("mermaid")) {
              // Replace "mermaid" with "mermaid-selfhost" in the class list
              node.properties.className = classNames.map((c: string) =>
                c === "mermaid" ? "mermaid-selfhost" : c
              )
            }
          }
          if (node.children) {
            for (const child of node.children) {
              visit(child)
            }
          }
        }
        visit(tree)
      },
    ]
  },
  externalResources() {
    return {
      js: [
        {
          loadTime: "afterDOMReady",
          contentType: "inline",
          script: `
(function () {
  var THEME_VARS = [
    "--secondary", "--tertiary", "--gray", "--light", "--lightgray",
    "--highlight", "--dark", "--darkgray", "--codeFont"
  ];
  var mermaidLoaded = false;
  var mermaidPromise = null;

  function getBasePath() {
    var bp = document.body.getAttribute("data-basepath");
    return bp ? bp : "";
  }

  function loadMermaid() {
    if (mermaidPromise) return mermaidPromise;
    mermaidPromise = new Promise(function (resolve, reject) {
      if (window.mermaid) { mermaidLoaded = true; resolve(window.mermaid); return; }
      var script = document.createElement("script");
      script.src = getBasePath() + "/static/mermaid.bundle.js";
      script.onload = function () {
        mermaidLoaded = true;
        resolve(window.mermaid);
      };
      script.onerror = function () {
        mermaidPromise = null;
        reject(new Error("Failed to load mermaid.bundle.js from " + script.src));
      };
      document.head.appendChild(script);
    });
    return mermaidPromise;
  }

  function removeChildren(el) {
    while (el.firstChild) el.removeChild(el.firstChild);
  }

  var PanZoom = (function () {
    function PanZoom(container, content) {
      this.container = container;
      this.content = content;
      this.isDragging = false;
      this.startPan = { x: 0, y: 0 };
      this.currentPan = { x: 0, y: 0 };
      this.scale = 1;
      this.MIN_SCALE = 0.5;
      this.MAX_SCALE = 3;
      this.cleanups = [];
      this.setupEventListeners();
      this.setupNavigationControls();
      this.resetTransform();
    }
    PanZoom.prototype.setupEventListeners = function () {
      var self = this;
      var onMouseDown = function (e) { self.onMouseDown(e); };
      var onMouseMove = function (e) { self.onMouseMove(e); };
      var onMouseUp = function (e) { self.onMouseUp(e); };
      var onTouchStart = function (e) { self.onTouchStart(e); };
      var onTouchMove = function (e) { self.onTouchMove(e); };
      var onTouchEnd = function (e) { self.onTouchEnd(e); };
      var onResize = function () { self.resetTransform(); };
      this.container.addEventListener("mousedown", onMouseDown);
      document.addEventListener("mousemove", onMouseMove);
      document.addEventListener("mouseup", onMouseUp);
      this.container.addEventListener("touchstart", onTouchStart, { passive: false });
      document.addEventListener("touchmove", onTouchMove, { passive: false });
      document.addEventListener("touchend", onTouchEnd);
      window.addEventListener("resize", onResize);
      this.cleanups.push(
        function () { self.container.removeEventListener("mousedown", onMouseDown); },
        function () { document.removeEventListener("mousemove", onMouseMove); },
        function () { document.removeEventListener("mouseup", onMouseUp); },
        function () { self.container.removeEventListener("touchstart", onTouchStart); },
        function () { document.removeEventListener("touchmove", onTouchMove); },
        function () { document.removeEventListener("touchend", onTouchEnd); },
        function () { window.removeEventListener("resize", onResize); }
      );
    };
    PanZoom.prototype.cleanup = function () { for (var i = 0; i < this.cleanups.length; i++) this.cleanups[i](); };
    PanZoom.prototype.setupNavigationControls = function () {
      var self = this;
      var controls = document.createElement("div");
      controls.className = "mermaid-controls";
      var zoomIn = this.createButton("+", function () { self.zoom(0.1); });
      var zoomOut = this.createButton("-", function () { self.zoom(-0.1); });
      var reset = this.createButton("Reset", function () { self.resetTransform(); });
      controls.appendChild(zoomOut);
      controls.appendChild(reset);
      controls.appendChild(zoomIn);
      this.container.appendChild(controls);
    };
    PanZoom.prototype.createButton = function (text, onClick) {
      var btn = document.createElement("button");
      btn.textContent = text;
      btn.className = "mermaid-control-button";
      btn.addEventListener("click", onClick);
      var cleanup = function () { btn.removeEventListener("click", onClick); };
      if (window.addCleanup) window.addCleanup(cleanup);
      return btn;
    };
    PanZoom.prototype.onMouseDown = function (e) {
      if (e.button !== 0) return;
      this.isDragging = true;
      this.startPan = { x: e.clientX - this.currentPan.x, y: e.clientY - this.currentPan.y };
      this.container.style.cursor = "grabbing";
    };
    PanZoom.prototype.onMouseMove = function (e) {
      if (!this.isDragging) return;
      e.preventDefault();
      this.currentPan = { x: e.clientX - this.startPan.x, y: e.clientY - this.startPan.y };
      this.updateTransform();
    };
    PanZoom.prototype.onMouseUp = function () { this.isDragging = false; this.container.style.cursor = "grab"; };
    PanZoom.prototype.onTouchStart = function (e) {
      if (e.touches.length !== 1) return;
      this.isDragging = true;
      var t = e.touches[0];
      this.startPan = { x: t.clientX - this.currentPan.x, y: t.clientY - this.currentPan.y };
    };
    PanZoom.prototype.onTouchMove = function (e) {
      if (!this.isDragging || e.touches.length !== 1) return;
      e.preventDefault();
      var t = e.touches[0];
      this.currentPan = { x: t.clientX - this.startPan.x, y: t.clientY - this.startPan.y };
      this.updateTransform();
    };
    PanZoom.prototype.onTouchEnd = function () { this.isDragging = false; };
    PanZoom.prototype.zoom = function (delta) {
      var newScale = Math.min(Math.max(this.scale + delta, this.MIN_SCALE), this.MAX_SCALE);
      var rect = this.content.getBoundingClientRect();
      var cx = rect.width / 2, cy = rect.height / 2;
      var d = newScale - this.scale;
      this.currentPan.x -= cx * d;
      this.currentPan.y -= cy * d;
      this.scale = newScale;
      this.updateTransform();
    };
    PanZoom.prototype.updateTransform = function () {
      this.content.style.transform = "translate(" + this.currentPan.x + "px, " + this.currentPan.y + "px) scale(" + this.scale + ")";
    };
    PanZoom.prototype.resetTransform = function () {
      var svg = this.content.querySelector("svg");
      if (!svg) return;
      var rect = svg.getBoundingClientRect();
      var w = rect.width / this.scale, h = rect.height / this.scale;
      this.scale = 1;
      this.currentPan = {
        x: (this.container.clientWidth - w) / 2,
        y: (this.container.clientHeight - h) / 2
      };
      this.updateTransform();
    };
    return PanZoom;
  })();

  function setupModal(node, expandBtn) {
    var modal = document.getElementById("mermaid-container");
    if (!modal) return;
    var space = modal.querySelector("#mermaid-space");
    var content = modal.querySelector(".mermaid-content");
    if (!content) return;
    var panZoom = null;

    var open = function () {
      removeChildren(content);
      var svgClone = node.querySelector("svg");
      if (svgClone) svgClone = svgClone.cloneNode(true);
      if (svgClone) content.appendChild(svgClone);
      modal.classList.add("active");
      if (space) space.style.cursor = "grab";
      panZoom = new PanZoom(space, content);
    };

    var close = function () {
      modal.classList.remove("active");
      if (panZoom) { panZoom.cleanup(); panZoom = null; }
    };

    expandBtn.addEventListener("click", open);
    modal.addEventListener("click", function (e) {
      if (e.target === modal || e.target === space) close();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key.startsWith("Esc") && modal.classList.contains("active")) close();
    });
    if (window.addCleanup) {
      window.addCleanup(function () { expandBtn.removeEventListener("click", open); });
    }
  }

  async function renderMermaid() {
    var nodes = document.querySelector(".center");
    if (!nodes) return;
    var elements = nodes.querySelectorAll("code.mermaid-selfhost");
    if (elements.length === 0) return;

    var mermaid = await loadMermaid();

    // Save original innerText for re-rendering on theme change
    var originalText = new WeakMap();
    for (var i = 0; i < elements.length; i++) {
      originalText.set(elements[i], elements[i].innerText);
    }

    async function doRender() {
      // Reset previous renders
      for (var i = 0; i < elements.length; i++) {
        elements[i].removeAttribute("data-processed");
        var orig = originalText.get(elements[i]);
        if (orig) elements[i].innerHTML = orig;
      }

      // Read theme CSS variables
      var vars = {};
      for (var j = 0; j < THEME_VARS.length; j++) {
        vars[THEME_VARS[j]] = window.getComputedStyle(document.documentElement).getPropertyValue(THEME_VARS[j]);
      }
      var isDark = document.documentElement.getAttribute("saved-theme") === "dark";

      mermaid.initialize({
        startOnLoad: false,
        securityLevel: "loose",
        theme: isDark ? "dark" : "base",
        themeVariables: {
          fontFamily: vars["--codeFont"],
          primaryColor: vars["--light"],
          primaryTextColor: vars["--darkgray"],
          primaryBorderColor: vars["--tertiary"],
          lineColor: vars["--darkgray"],
          secondaryColor: vars["--secondary"],
          tertiaryColor: vars["--tertiary"],
          clusterBkg: vars["--light"],
          edgeLabelBackground: vars["--highlight"]
        }
      });

      await mermaid.run({ nodes: elements });

      // Wire up expand buttons
      for (var k = 0; k < elements.length; k++) {
        var node = elements[k];
        var parent = node.parentElement;
        if (!parent) continue;
        var expandBtn = parent.querySelector(".expand-button");
        if (expandBtn) {
          // Position expand button relative to clipboard button
          var clipBtn = parent.querySelector(".clipboard-button");
          if (clipBtn) {
            var cs = window.getComputedStyle(clipBtn);
            var clipW = clipBtn.offsetWidth + parseFloat(cs.marginLeft || "0") + parseFloat(cs.marginRight || "0");
            expandBtn.style.right = "calc(" + clipW + "px + 0.3rem)";
          }
          parent.prepend(expandBtn);
          setupModal(node, expandBtn);
        }
      }
    }

    await doRender();

    // Re-render on theme change
    document.addEventListener("themechange", doRender);
    if (window.addCleanup) {
      window.addCleanup(function () { document.removeEventListener("themechange", doRender); });
    }
  }

  document.addEventListener("nav", renderMermaid);
  document.addEventListener("render", renderMermaid);
})();
`,
        } as JSResource,
      ],
    }
  },
})

export default MermaidSelfhost
