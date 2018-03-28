/**
 * PageObject.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PageObject  implements java.io.Serializable {
    private org.apache.axis.types.UnsignedInt issuePagePosition;

    private org.apache.axis.types.UnsignedInt pageOrder;

    private java.lang.String pageNumber;

    private org.apache.axis.types.UnsignedInt pageSequence;

    private double height;

    private double width;

    private java.lang.String parentLayoutId;

    private boolean outputRenditionAvailable;

    private com.woodwing.enterprise.interfaces.services.wfl.PlacementInfo[] placementInfos;

    public PageObject() {
    }

    public PageObject(
           org.apache.axis.types.UnsignedInt issuePagePosition,
           org.apache.axis.types.UnsignedInt pageOrder,
           java.lang.String pageNumber,
           org.apache.axis.types.UnsignedInt pageSequence,
           double height,
           double width,
           java.lang.String parentLayoutId,
           boolean outputRenditionAvailable,
           com.woodwing.enterprise.interfaces.services.wfl.PlacementInfo[] placementInfos) {
           this.issuePagePosition = issuePagePosition;
           this.pageOrder = pageOrder;
           this.pageNumber = pageNumber;
           this.pageSequence = pageSequence;
           this.height = height;
           this.width = width;
           this.parentLayoutId = parentLayoutId;
           this.outputRenditionAvailable = outputRenditionAvailable;
           this.placementInfos = placementInfos;
    }


    /**
     * Gets the issuePagePosition value for this PageObject.
     * 
     * @return issuePagePosition
     */
    public org.apache.axis.types.UnsignedInt getIssuePagePosition() {
        return issuePagePosition;
    }


    /**
     * Sets the issuePagePosition value for this PageObject.
     * 
     * @param issuePagePosition
     */
    public void setIssuePagePosition(org.apache.axis.types.UnsignedInt issuePagePosition) {
        this.issuePagePosition = issuePagePosition;
    }


    /**
     * Gets the pageOrder value for this PageObject.
     * 
     * @return pageOrder
     */
    public org.apache.axis.types.UnsignedInt getPageOrder() {
        return pageOrder;
    }


    /**
     * Sets the pageOrder value for this PageObject.
     * 
     * @param pageOrder
     */
    public void setPageOrder(org.apache.axis.types.UnsignedInt pageOrder) {
        this.pageOrder = pageOrder;
    }


    /**
     * Gets the pageNumber value for this PageObject.
     * 
     * @return pageNumber
     */
    public java.lang.String getPageNumber() {
        return pageNumber;
    }


    /**
     * Sets the pageNumber value for this PageObject.
     * 
     * @param pageNumber
     */
    public void setPageNumber(java.lang.String pageNumber) {
        this.pageNumber = pageNumber;
    }


    /**
     * Gets the pageSequence value for this PageObject.
     * 
     * @return pageSequence
     */
    public org.apache.axis.types.UnsignedInt getPageSequence() {
        return pageSequence;
    }


    /**
     * Sets the pageSequence value for this PageObject.
     * 
     * @param pageSequence
     */
    public void setPageSequence(org.apache.axis.types.UnsignedInt pageSequence) {
        this.pageSequence = pageSequence;
    }


    /**
     * Gets the height value for this PageObject.
     * 
     * @return height
     */
    public double getHeight() {
        return height;
    }


    /**
     * Sets the height value for this PageObject.
     * 
     * @param height
     */
    public void setHeight(double height) {
        this.height = height;
    }


    /**
     * Gets the width value for this PageObject.
     * 
     * @return width
     */
    public double getWidth() {
        return width;
    }


    /**
     * Sets the width value for this PageObject.
     * 
     * @param width
     */
    public void setWidth(double width) {
        this.width = width;
    }


    /**
     * Gets the parentLayoutId value for this PageObject.
     * 
     * @return parentLayoutId
     */
    public java.lang.String getParentLayoutId() {
        return parentLayoutId;
    }


    /**
     * Sets the parentLayoutId value for this PageObject.
     * 
     * @param parentLayoutId
     */
    public void setParentLayoutId(java.lang.String parentLayoutId) {
        this.parentLayoutId = parentLayoutId;
    }


    /**
     * Gets the outputRenditionAvailable value for this PageObject.
     * 
     * @return outputRenditionAvailable
     */
    public boolean isOutputRenditionAvailable() {
        return outputRenditionAvailable;
    }


    /**
     * Sets the outputRenditionAvailable value for this PageObject.
     * 
     * @param outputRenditionAvailable
     */
    public void setOutputRenditionAvailable(boolean outputRenditionAvailable) {
        this.outputRenditionAvailable = outputRenditionAvailable;
    }


    /**
     * Gets the placementInfos value for this PageObject.
     * 
     * @return placementInfos
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PlacementInfo[] getPlacementInfos() {
        return placementInfos;
    }


    /**
     * Sets the placementInfos value for this PageObject.
     * 
     * @param placementInfos
     */
    public void setPlacementInfos(com.woodwing.enterprise.interfaces.services.wfl.PlacementInfo[] placementInfos) {
        this.placementInfos = placementInfos;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PageObject)) return false;
        PageObject other = (PageObject) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.issuePagePosition==null && other.getIssuePagePosition()==null) || 
             (this.issuePagePosition!=null &&
              this.issuePagePosition.equals(other.getIssuePagePosition()))) &&
            ((this.pageOrder==null && other.getPageOrder()==null) || 
             (this.pageOrder!=null &&
              this.pageOrder.equals(other.getPageOrder()))) &&
            ((this.pageNumber==null && other.getPageNumber()==null) || 
             (this.pageNumber!=null &&
              this.pageNumber.equals(other.getPageNumber()))) &&
            ((this.pageSequence==null && other.getPageSequence()==null) || 
             (this.pageSequence!=null &&
              this.pageSequence.equals(other.getPageSequence()))) &&
            this.height == other.getHeight() &&
            this.width == other.getWidth() &&
            ((this.parentLayoutId==null && other.getParentLayoutId()==null) || 
             (this.parentLayoutId!=null &&
              this.parentLayoutId.equals(other.getParentLayoutId()))) &&
            this.outputRenditionAvailable == other.isOutputRenditionAvailable() &&
            ((this.placementInfos==null && other.getPlacementInfos()==null) || 
             (this.placementInfos!=null &&
              java.util.Arrays.equals(this.placementInfos, other.getPlacementInfos())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getIssuePagePosition() != null) {
            _hashCode += getIssuePagePosition().hashCode();
        }
        if (getPageOrder() != null) {
            _hashCode += getPageOrder().hashCode();
        }
        if (getPageNumber() != null) {
            _hashCode += getPageNumber().hashCode();
        }
        if (getPageSequence() != null) {
            _hashCode += getPageSequence().hashCode();
        }
        _hashCode += new Double(getHeight()).hashCode();
        _hashCode += new Double(getWidth()).hashCode();
        if (getParentLayoutId() != null) {
            _hashCode += getParentLayoutId().hashCode();
        }
        _hashCode += (isOutputRenditionAvailable() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getPlacementInfos() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPlacementInfos());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPlacementInfos(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PageObject.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PageObject"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issuePagePosition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IssuePagePosition"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageNumber");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageNumber"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequence");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequence"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("height");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Height"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("width");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Width"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("parentLayoutId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ParentLayoutId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("outputRenditionAvailable");
        elemField.setXmlName(new javax.xml.namespace.QName("", "OutputRenditionAvailable"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("placementInfos");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PlacementInfos"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PlacementInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PlacementInfo"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
