import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";

import "@/components/feature-search.css";
import { isFavorite, toggleFavorite } from "@/tool/favorites";
import { searchIndex } from "@/tool/featureIndex";
import { __, sprintf } from "@/tool/i18n";
import type { SearchItem } from "@/tool/featureIndex";

interface FeatureSearchProps {
  onNavigate: (tabKey: string, itemId: string) => void;
  className?: string;
  style?: React.CSSProperties;
}

const tagClassMap: Record<string, string> = {
  [__("推荐")]: "success",
  "SEO": "info",
  [__("安全")]: "danger",
  [__("性能")]: "warning",
  [__("谨慎")]: "caution",
  [__("仅前台")]: "purple",
  [__("仅后台")]: "cyan",
  [__("需主题兼容")]: "gold",
};

const FeatureSearch: React.FC<FeatureSearchProps> = ({ onNavigate, className, style }) => {
  const [keyword, setKeyword] = useState("");
  const [open, setOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState(-1);
  const [, refreshFavorites] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);
  const resultRefs = useRef<Array<HTMLButtonElement | null>>([]);

  const results = useMemo(() => {
    if (!keyword.trim()) return [];
    const normalizedKeyword = keyword.toLowerCase().trim();

    return searchIndex.filter(
      (item) =>
        item.label.toLowerCase().includes(normalizedKeyword) ||
        item.keywords?.some((itemKeyword) =>
          itemKeyword.toLowerCase().includes(normalizedKeyword),
        ) ||
        item.section?.toLowerCase().includes(normalizedKeyword) ||
        item.aliases?.some((alias) => alias.toLowerCase().includes(normalizedKeyword)),
    );
  }, [keyword]);

  const visibleResults = results.slice(0, 20);
  const hasKeyword = keyword.trim().length > 0;
  const showResults = open && visibleResults.length > 0;
  const showEmpty = open && hasKeyword && results.length === 0;
  const panelVisible = showResults || showEmpty;

  useEffect(() => {
    if (!showResults || activeIndex < 0) return;
    resultRefs.current[activeIndex]?.focus();
  }, [activeIndex, showResults]);

  const closeResults = useCallback(() => {
    setOpen(false);
    setActiveIndex(-1);
  }, []);

  const handleSelect = useCallback(
    (item: SearchItem) => {
      onNavigate(item.tabKey, item.id);
      setKeyword("");
      closeResults();
    },
    [closeResults, onNavigate],
  );

  const clearSearch = () => {
    setKeyword("");
    closeResults();
    inputRef.current?.focus();
  };

  const focusResult = (index: number) => {
    if (visibleResults.length === 0) return;
    const nextIndex = (index + visibleResults.length) % visibleResults.length;
    setActiveIndex(nextIndex);
  };

  const handleInputKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (event.key === "Escape") {
      event.preventDefault();
      closeResults();
      return;
    }

    if (event.key === "ArrowDown" && visibleResults.length > 0) {
      event.preventDefault();
      setOpen(true);
      focusResult(0);
    } else if (event.key === "ArrowUp" && visibleResults.length > 0) {
      event.preventDefault();
      setOpen(true);
      focusResult(visibleResults.length - 1);
    }
  };

  const handleResultKeyDown = (
    event: React.KeyboardEvent<HTMLButtonElement>,
    index: number,
  ) => {
    if (event.key === "Escape") {
      event.preventDefault();
      inputRef.current?.focus();
      closeResults();
    } else if (event.key === "ArrowDown") {
      event.preventDefault();
      focusResult(index + 1);
    } else if (event.key === "ArrowUp") {
      event.preventDefault();
      focusResult(index - 1);
    } else if (event.key === "Home") {
      event.preventDefault();
      focusResult(0);
    } else if (event.key === "End") {
      event.preventDefault();
      focusResult(visibleResults.length - 1);
    }
  };

  const highlightText = (text: string) => {
    const normalizedKeyword = keyword.trim();
    if (!normalizedKeyword) return text;

    const matchIndex = text.toLowerCase().indexOf(normalizedKeyword.toLowerCase());
    if (matchIndex === -1) return text;

    return (
      <>
        {text.slice(0, matchIndex)}
        <mark className="mabox-feature-search-highlight">
          {text.slice(matchIndex, matchIndex + normalizedKeyword.length)}
        </mark>
        {text.slice(matchIndex + normalizedKeyword.length)}
      </>
    );
  };

  return (
    <div
      className={["mabox-feature-search", className].filter(Boolean).join(" ")}
      onBlur={(event) => {
        if (!event.currentTarget.contains(event.relatedTarget as Node | null)) closeResults();
      }}
      style={style}
    >
      <div className="mabox-feature-search-control" role="search">
        <span
          className="dashicons dashicons-search mabox-feature-search-leading-icon"
          aria-hidden="true"
        />
        <input
          ref={inputRef}
          type="search"
          className="mabox-feature-search-input"
          aria-controls={panelVisible ? "mabox-feature-search-results" : undefined}
          aria-expanded={panelVisible}
          aria-label={__("搜索功能或设置")}
          placeholder={__("搜索功能或设置...")}
          value={keyword}
          onChange={(event) => {
            setKeyword(event.target.value);
            setOpen(true);
            setActiveIndex(-1);
          }}
          onFocus={() => setOpen(true)}
          onKeyDown={handleInputKeyDown}
        />
        {hasKeyword && (
          <button
            type="button"
            className="mabox-feature-search-clear"
            aria-label={__("清空搜索")}
            onClick={clearSearch}
          >
            <span className="dashicons dashicons-dismiss" aria-hidden="true" />
          </button>
        )}
      </div>

      {panelVisible && (
        <div id="mabox-feature-search-results" className="mabox-feature-search-panel">
          {showResults ? (
            <>
              <div className="mabox-feature-search-count" aria-live="polite">
                {sprintf(__("找到 %d 项，显示前 %d 项"), results.length, visibleResults.length)}
              </div>
              <ul className="mabox-feature-search-results" aria-label={__("功能搜索结果")}>
                {visibleResults.map((item, index) => {
                  const favorite = isFavorite(item.id);

                  return (
                    <li
                      className={`mabox-feature-search-result ${activeIndex === index ? "mabox-feature-search-result--active" : ""}`}
                      key={item.id}
                    >
                      <button
                        type="button"
                        className="mabox-feature-search-favorite"
                          aria-label={`${favorite ? __("取消收藏") : __("收藏")}${item.label}`}
                        aria-pressed={favorite}
                        onClick={() => {
                          toggleFavorite(item.id);
                          refreshFavorites((revision) => revision + 1);
                        }}
                      >
                        <span
                          className={`dashicons ${favorite ? "dashicons-star-filled" : "dashicons-star-empty"}`}
                          aria-hidden="true"
                        />
                      </button>
                      <button
                        ref={(element) => {
                          resultRefs.current[index] = element;
                        }}
                        type="button"
                        className="mabox-feature-search-open"
                        aria-label={`${__("打开")}${item.label}`}
                        onClick={() => handleSelect(item)}
                        onFocus={() => setActiveIndex(index)}
                        onKeyDown={(event) => handleResultKeyDown(event, index)}
                      >
                        <span className="mabox-feature-search-label">
                          {highlightText(item.label)}
                        </span>
                        {item.tags && item.tags.length > 0 && (
                          <span className="mabox-feature-search-tags" aria-label={__("功能标签")}>
                            {item.tags.map((tag) => (
                              <span
                                className={`mabox-feature-search-tag mabox-feature-search-tag--${tagClassMap[tag] || "default"}`}
                                key={tag}
                              >
                                {tag}
                              </span>
                            ))}
                          </span>
                        )}
                      </button>
                    </li>
                  );
                })}
              </ul>
            </>
          ) : (
            <div className="mabox-feature-search-empty" role="status" aria-live="polite">
              {__("未找到匹配的功能")}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default FeatureSearch;
