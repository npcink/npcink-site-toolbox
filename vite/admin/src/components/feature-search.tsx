import React, { useState, useEffect, useMemo, useCallback } from "react";
import { Input, List, Typography, Tag, Button } from "antd";
import { SearchOutlined, StarOutlined, StarFilled } from "@ant-design/icons";
import { isFavorite, toggleFavorite } from "@/tool/favorites";
import { fetchFeatureIndex } from "@/tool/featureIndex";
import { SearchItem, searchIndex } from "@/tool/featureIndexData";

const { Text } = Typography;

interface FeatureSearchProps {
  onNavigate: (tabKey: string, itemId: string) => void;
  className?: string;
  style?: React.CSSProperties;
}

const FeatureSearch: React.FC<FeatureSearchProps> = ({ onNavigate, className, style }) => {
  const [keyword, setKeyword] = useState("");
  const [open, setOpen] = useState(false);
  const [favRefresh, setFavRefresh] = useState(0);
  const [mergedIndex, setMergedIndex] = useState<SearchItem[]>(searchIndex);

  useEffect(() => {
    fetchFeatureIndex().then((merged) => {
      setMergedIndex(merged);
    });
  }, []);

  const handleToggleFavorite = (e: React.MouseEvent, itemId: string) => {
    e.stopPropagation();
    toggleFavorite(itemId);
    setFavRefresh((k) => k + 1);
  };

  useMemo(() => {
    return favRefresh;
  }, [favRefresh]);

  const results = useMemo(() => {
    if (!keyword.trim()) return [];
    const kw = keyword.toLowerCase().trim();
    return mergedIndex.filter(
      (item) =>
        item.label.toLowerCase().includes(kw) ||
        (item.keywords && item.keywords.some((k) => k.toLowerCase().includes(kw))) ||
        (item.section && item.section.toLowerCase().includes(kw)) ||
        (item.aliases && item.aliases.some((a) => a.toLowerCase().includes(kw)))
    );
  }, [keyword, mergedIndex]);

  const handleSelect = useCallback(
    (item: SearchItem) => {
      onNavigate(item.tabKey, item.id);
      setKeyword("");
      setOpen(false);
    },
    [onNavigate]
  );

  const tagColorMap: Record<string, string> = {
    "推荐": "green",
    "SEO": "blue",
    "安全": "red",
    "性能": "orange",
    "谨慎": "volcano",
    "仅前台": "purple",
    "仅后台": "cyan",
    "需主题兼容": "gold",
  };

  const highlightText = (text: string) => {
    if (!keyword.trim()) return text;
    const idx = text.toLowerCase().indexOf(keyword.toLowerCase());
    if (idx === -1) return text;
    return (
      <>
        {text.slice(0, idx)}
        <Text strong style={{ color: "#1677ff" }}>
          {text.slice(idx, idx + keyword.length)}
        </Text>
        {text.slice(idx + keyword.length)}
      </>
    );
  };

  return (
    <div className={className} style={{ position: "relative", width: "100%", ...style }}>
      <Input
        aria-controls="mabox-feature-search-results"
        aria-expanded={open && results.length > 0}
        aria-label="搜索功能或设置"
        prefix={<SearchOutlined style={{ color: "#bfbfbf" }} />}
        placeholder="搜索功能或设置..."
        value={keyword}
        onChange={(e) => {
          setKeyword(e.target.value);
          setOpen(true);
        }}
        onFocus={() => setOpen(true)}
        allowClear
        style={{ borderRadius: 6 }}
      />
      {open && results.length > 0 && (
        <div
          id="mabox-feature-search-results"
          aria-label="功能搜索结果"
          style={{
            position: "absolute",
            top: "100%",
            left: 0,
            right: 0,
            zIndex: 1050,
            marginTop: 4,
            background: "#fff",
            borderRadius: 8,
            boxShadow: "0 6px 16px rgba(0,0,0,0.12)",
            maxHeight: 400,
            overflow: "auto",
          }}
        >
          <List
            size="small"
            dataSource={results.slice(0, 20)}
            renderItem={(item) => (
              <List.Item
                style={{
                  padding: "8px 16px",
                }}
              >
                <div style={{ display: "flex", alignItems: "center", gap: 8, width: "100%" }}>
                  <Button
                    aria-label={`${isFavorite(item.id) ? "取消收藏" : "收藏"}${item.label}`}
                    type="text"
                    size="small"
                    icon={isFavorite(item.id) ? <StarFilled style={{ color: "#faad14" }} /> : <StarOutlined />}
                    onClick={(e) => handleToggleFavorite(e, item.id)}
                    style={{ padding: 0, minWidth: 24 }}
                  />
                  <button
                    type="button"
                    aria-label={`打开${item.label}`}
                    onClick={() => handleSelect(item)}
                    style={{
                      alignItems: "center",
                      background: "transparent",
                      border: 0,
                      color: "inherit",
                      cursor: "pointer",
                      display: "flex",
                      flex: 1,
                      gap: 8,
                      minWidth: 0,
                      padding: "4px 0",
                      textAlign: "left",
                    }}
                  >
                    <span style={{ flex: 1 }}>{highlightText(item.label)}</span>
                    {item.tags && item.tags.map((tag) => (
                      <Tag color={tagColorMap[tag] || "default"} key={tag} style={{ margin: 0, fontSize: 11 }}>
                        {tag}
                      </Tag>
                    ))}
                  </button>
                </div>
              </List.Item>
            )}
          />
        </div>
      )}
      {open && keyword.trim() && results.length === 0 && (
        <div
          style={{
            position: "absolute",
            top: "100%",
            left: 0,
            right: 0,
            zIndex: 1050,
            marginTop: 4,
            background: "#fff",
            borderRadius: 8,
            boxShadow: "0 6px 16px rgba(0,0,0,0.12)",
            padding: "16px",
            textAlign: "center",
            color: "#999",
          }}
        >
          未找到匹配的功能
        </div>
      )}
    </div>
  );
};

export default FeatureSearch;
